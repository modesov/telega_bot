<?php

declare(strict_types=1);

namespace Modes\TelegaBot;

use Modes\TelegaBot\Contracts\StorageInterface;
use Modes\TelegaBot\Exceptions\ApiException;
use Modes\TelegaBot\Exceptions\HandlerNotFoundException;
use Modes\TelegaBot\Handlers\AbstractHandler;
use Modes\TelegaBot\Methods\GetUpdates;
use Modes\TelegaBot\Storage\FileStorage;
use Modes\TelegaBot\Types\Update;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Диспетчер входящих обновлений Telegram-бота.
 *
 * Отвечает за:
 * - Загрузку обработчиков из директории (или ручную регистрацию).
 * - Маршрутизацию входящих обновлений к подходящему обработчику.
 * - Запуск режима long polling.
 * - Обработку входящих webhook-запросов.
 *
 * ## Быстрый старт (polling)
 *
 * ```php
 * $bot = new Bot('YOUR_TOKEN');
 * $dispatcher = new Dispatcher($bot, pathHandlers: __DIR__ . '/handlers');
 * $dispatcher->runPoling();
 * ```
 *
 * ## Быстрый старт (webhook)
 *
 * ```php
 * // index.php (точка входа для webhook)
 * $bot = new Bot('YOUR_TOKEN');
 * $dispatcher = new Dispatcher($bot, pathHandlers: __DIR__ . '/handlers');
 * $dispatcher->runHook();
 * ```
 *
 * ## Ручная регистрация обработчиков
 *
 * ```php
 * $dispatcher
 *     ->addHandler(new StartHandler())
 *     ->addHandler(new HelpHandler());
 * ```
 */
final class Dispatcher
{
    /**
     * Зарегистрированные обработчики обновлений.
     *
     * @var AbstractHandler[]
     */
    private array $handlers = [];

    /**
     * Хранилище состояния (offset последнего обновления и т.д.).
     */
    private StorageInterface $storage;

    /**
     * PSR-3 логгер.
     */
    private LoggerInterface $logger;

    /**
     * @param Bot                  $bot          Экземпляр бота для отправки запросов.
     * @param string               $pathHandlers Путь к директории с файлами обработчиков.
     *                                            Если указан и директория существует — обработчики
     *                                            загружаются автоматически при создании диспетчера.
     * @param StorageInterface|null $storage     Хранилище состояния. По умолчанию — {@see FileStorage}.
     * @param LoggerInterface|null  $logger      PSR-3 логгер. По умолчанию — NullLogger.
     * @param bool                  $throwOnMissedHandler Если true — бросает {@see HandlerNotFoundException},
     *                                                     когда ни один обработчик не подошёл для обновления.
     * @param int                   $pollingInterval Интервал между запросами getUpdates в секундах (по умолчанию 1).
     */
    public function __construct(
        private readonly Bot $bot,
        private readonly string $pathHandlers = "",
        ?StorageInterface $storage = null,
        ?LoggerInterface $logger = null,
        private readonly bool $throwOnMissedHandler = false,
        private readonly int $pollingInterval = 1,
    ) {
        $this->storage = $storage ?? new FileStorage();
        $this->logger = $logger ?? new NullLogger();

        if ($this->pathHandlers !== "" && is_dir($this->pathHandlers)) {
            $this->loadHandlers();
        }
    }

    /**
     * Регистрирует обработчик обновлений.
     *
     * Обработчики проверяются в порядке добавления. Первый подходящий — выигрывает.
     *
     * @param AbstractHandler $handler Обработчик для регистрации.
     *
     * @return static Возвращает себя для цепочки вызовов.
     */
    public function addHandler(AbstractHandler $handler): static
    {
        $this->handlers[] = $handler;

        $this->logger->debug("Handler registered", [
            "handler" => get_class($handler),
        ]);

        return $this;
    }

    /**
     * Возвращает список всех зарегистрированных обработчиков.
     *
     * @return AbstractHandler[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Загружает обработчики из PHP-файлов директории {@see $pathHandlers}.
     *
     * Каждый файл должен возвращать экземпляр {@see AbstractHandler}.
     * Файлы, не возвращающие обработчик, молча игнорируются.
     *
     * Пример файла обработчика:
     *
     * ```php
     * // handlers/StartHandler.php
     * use Modes\TelegaBot\Handlers\AbstractCommandHandler;
     *
     * return new class extends AbstractCommandHandler {
     *     protected function getCommand(): string { return '/start'; }
     *
     *     public function handle(Update $update, Bot $bot): void {
     *         $bot->send(new SendMessage($update->message->chat->id, 'Привет!'));
     *     }
     * };
     * ```
     *
     * @return static Возвращает себя для цепочки вызовов.
     */
    public function loadHandlers(): static
    {
        if ($this->pathHandlers === "" || !is_dir($this->pathHandlers)) {
            $this->logger->warning("Handlers directory not found or not set", [
                "path" => $this->pathHandlers,
            ]);

            return $this;
        }

        $files = glob(rtrim($this->pathHandlers, "/") . "/*.php");

        if ($files === false || empty($files)) {
            $this->logger->info("No handler files found in directory", [
                "path" => $this->pathHandlers,
            ]);

            return $this;
        }

        foreach ($files as $file) {
            $handler = require $file;

            if ($handler instanceof AbstractHandler) {
                $this->addHandler($handler);
            } else {
                $this->logger->warning(
                    "Handler file did not return an AbstractHandler instance",
                    [
                        "file" => $file,
                    ],
                );
            }
        }

        $this->logger->info("Handlers loaded from directory", [
            "path" => $this->pathHandlers,
            "count" => count($this->handlers),
        ]);

        return $this;
    }

    /**
     * Запускает бота в режиме long polling.
     *
     * Работает в бесконечном цикле: запрашивает обновления через getUpdates,
     * диспетчеризирует каждое обновление, затем делает паузу {@see $pollingInterval} секунд.
     *
     * Для остановки бота используйте Ctrl+C (SIGINT) или завершите процесс.
     *
     * @param int $limit   Максимальное количество обновлений за один запрос (1–100).
     * @param int $timeout Таймаут long polling в секундах. 0 — short polling.
     */
    public function runPoling(int $limit = 100, int $timeout = 0): void
    {
        $this->logger->info("Starting polling", [
            "limit" => $limit,
            "timeout" => $timeout,
            "interval" => $this->pollingInterval,
            "handlers" => count($this->handlers),
        ]);

        while (true) {
            try {
                $response = $this->bot->send(
                    new GetUpdates($this->storage, $limit, $timeout),
                );

                if ($response?->ok && is_array($response->result)) {
                    foreach ($response->result as $update) {
                        $this->dispatchSafely($update);
                    }
                }
            } catch (ApiException $e) {
                $this->logger->error("Telegram API error during polling", [
                    "error_code" => $e->getErrorCode(),
                    "message" => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error("Unexpected error during polling", [
                    "message" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]);
            }

            if ($this->pollingInterval > 0) {
                sleep($this->pollingInterval);
            }
        }
    }

    /**
     * Обрабатывает одно входящее обновление в режиме webhook.
     *
     * Читает тело запроса из php://input, десериализует JSON
     * и передаёт обновление в {@see dispatch()}.
     *
     * Используется как точка входа для webhook-endpoint'а:
     *
     * ```php
     * // index.php
     * $dispatcher->runHook();
     * ```
     */
    public function runHook(): void
    {
        $this->logger->info("Processing incoming webhook request");

        $input = file_get_contents("php://input");

        if ($input === false || $input === "") {
            $this->logger->warning("Empty or unreadable webhook body received");
            return;
        }

        try {
            $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error("Failed to decode webhook JSON body", [
                "message" => $e->getMessage(),
            ]);
            return;
        }

        if (!isset($data["update_id"])) {
            $this->logger->warning(
                "Webhook payload does not contain update_id",
                [
                    "data" => $data,
                ],
            );
            return;
        }

        $update = Update::fromArray($data);

        $this->dispatchSafely($update);
    }

    /**
     * Диспетчеризирует обновление к подходящему обработчику.
     *
     * Перебирает зарегистрированные обработчики в порядке добавления.
     * Первый, чей {@see AbstractHandler::supports()} вернул true — вызывается.
     * Остальные — игнорируются.
     *
     * @param Update $update Обработанное обновление.
     *
     * @throws HandlerNotFoundException Если {@see $throwOnMissedHandler} = true
     *                                  и ни один обработчик не подошёл.
     */
    public function dispatch(Update $update): void
    {
        $this->logger->debug("Dispatching update", [
            "update_id" => $update->updateId,
        ]);

        foreach ($this->handlers as $handler) {
            if ($handler->supports($update)) {
                $this->logger->debug("Handler matched", [
                    "update_id" => $update->updateId,
                    "handler" => get_class($handler),
                ]);

                $handler->handle($update, $this->bot);
                return;
            }
        }

        $this->logger->debug("No handler matched for update", [
            "update_id" => $update->updateId,
        ]);

        if ($this->throwOnMissedHandler) {
            throw new HandlerNotFoundException($update->updateId);
        }
    }

    /**
     * Диспетчеризирует обновление, перехватывая все исключения.
     *
     * Используется внутри polling-цикла и webhook для предотвращения
     * падения бота из-за ошибки в одном обработчике.
     *
     * @param Update $update Входящее обновление.
     */
    private function dispatchSafely(Update $update): void
    {
        try {
            $this->dispatch($update);
        } catch (HandlerNotFoundException $e) {
            $this->logger->warning($e->getMessage());
        } catch (ApiException $e) {
            $this->logger->error("Telegram API error in handler", [
                "update_id" => $update->updateId,
                "error_code" => $e->getErrorCode(),
                "message" => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected error in handler", [
                "update_id" => $update->updateId,
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
        }
    }
}
