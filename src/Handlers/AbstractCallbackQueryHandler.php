<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Handlers;

use Modes\TelegaBot\Types\Update;

/**
 * Абстрактный обработчик callback-запросов от инлайн-кнопок.
 *
 * Срабатывает когда пользователь нажимает на инлайн-кнопку с callback_data.
 * Поддерживает как точное совпадение, так и совпадение по префиксу.
 *
 * Пример использования (точное совпадение):
 *
 * ```php
 * // handlers/ApproveCallbackHandler.php
 * return new class extends AbstractCallbackQueryHandler {
 *     protected function getCallbackData(): string
 *     {
 *         return 'approve';
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         $query = $update->callbackQuery;
 *
 *         $bot->send(new AnswerCallbackQuery($query->id, 'Принято!'));
 *         $bot->send(new SendMessage($query->message->chat->id, 'Вы подтвердили действие.'));
 *     }
 * };
 * ```
 *
 * Пример использования (совпадение по префиксу):
 *
 * ```php
 * // handlers/ItemCallbackHandler.php
 * return new class extends AbstractCallbackQueryHandler {
 *     protected function getCallbackData(): string
 *     {
 *         return 'item:';
 *     }
 *
 *     protected function matchByPrefix(): bool
 *     {
 *         return true;
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         // Получаем часть после префикса, например '42' из 'item:42'
 *         $itemId = $this->getPayload($update);
 *
 *         $bot->send(new AnswerCallbackQuery($update->callbackQuery->id));
 *         $bot->send(new SendMessage(
 *             $update->callbackQuery->message->chat->id,
 *             "Вы выбрали item #$itemId",
 *         ));
 *     }
 * };
 * ```
 */
abstract class AbstractCallbackQueryHandler extends AbstractHandler
{
    /**
     * Возвращает строку callback_data (или её префикс), которую обрабатывает данный обработчик.
     *
     * @return string Например: 'approve', 'delete', 'item:'.
     */
    abstract protected function getCallbackData(): string;

    /**
     * Если возвращает true — сравнение идёт по префиксу (str_starts_with).
     * Если false (по умолчанию) — требуется точное совпадение.
     */
    protected function matchByPrefix(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Возвращает true если обновление является callback-запросом
     * и его data совпадает с {@see getCallbackData()} (точно или по префиксу).
     */
    public function supports(Update $update): bool
    {
        if (!$update->isCallbackQuery()) {
            return false;
        }

        $data = $update->callbackQuery?->data;

        if ($data === null) {
            return false;
        }

        if ($this->matchByPrefix()) {
            return str_starts_with($data, $this->getCallbackData());
        }

        return $data === $this->getCallbackData();
    }

    /**
     * Возвращает часть callback_data после префикса.
     *
     * Полезно при использовании {@see matchByPrefix()} = true.
     * Например, если prefix = 'item:' и data = 'item:42', вернёт '42'.
     *
     * Если совпадение идёт не по префиксу — возвращает полную строку data.
     *
     * @param Update $update Входящее обновление.
     */
    protected function getPayload(Update $update): string
    {
        $data = $update->callbackQuery?->data ?? '';

        if ($this->matchByPrefix()) {
            return substr($data, strlen($this->getCallbackData()));
        }

        return $data;
    }
}
