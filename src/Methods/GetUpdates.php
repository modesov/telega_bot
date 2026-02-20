<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MethodsInterface;
use Modes\TelegaBot\Contracts\StorageInterface;
use Modes\TelegaBot\Types\Update;

/**
 * Метод getUpdates — получение входящих обновлений через long polling.
 *
 * Использует {@see StorageInterface} для сохранения идентификатора
 * последнего обработанного обновления между запросами.
 *
 * @see https://core.telegram.org/bots/api#getupdates
 */
class GetUpdates implements MethodsInterface
{
    private const STORAGE_KEY = "last_update_id";

    /**
     * @param StorageInterface $storage Хранилище для offset последнего обновления.
     * @param int              $limit   Максимальное количество обновлений за один запрос (1–100).
     * @param int              $timeout Таймаут long polling в секундах (0 — short polling).
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly int $limit = 100,
        private readonly int $timeout = 0,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "getUpdates";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $lastUpdateId = $this->storage->get(self::STORAGE_KEY, 0);
        $offset = $lastUpdateId > 0 ? $lastUpdateId + 1 : 0;

        return [
            "offset" => $offset,
            "limit" => $this->limit,
            "timeout" => $this->timeout,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Парсит массив обновлений и сохраняет идентификатор последнего
     * в хранилище для корректного offset следующего запроса.
     */
    public function getResponse(array $data): BotResponse
    {
        if (!$data["ok"] || empty($data["result"])) {
            return new BotResponse(ok: $data["ok"], result: []);
        }

        $result = array_map(
            fn(array $update) => Update::fromArray($update),
            $data["result"],
        );

        $lastUpdate = end($result);
        $this->storage->set(self::STORAGE_KEY, $lastUpdate->updateId);

        return new BotResponse(ok: true, result: $result);
    }
}
