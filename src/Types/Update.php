<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет входящее обновление от Telegram.
 *
 * Каждое обновление содержит ровно одно из необязательных полей:
 * message, callback_query и т.д. Все они nullable.
 *
 * @see https://core.telegram.org/bots/api#update
 */
final readonly class Update
{
    /**
     * @param int               $updateId      Уникальный идентификатор обновления.
     * @param Message|null      $message       Новое входящее сообщение (если есть).
     * @param Message|null      $editedMessage Отредактированное сообщение (если есть).
     * @param CallbackQuery|null $callbackQuery Входящий callback-запрос от инлайн-кнопки (если есть).
     */
    public function __construct(
        public int $updateId,
        public ?Message $message = null,
        public ?Message $editedMessage = null,
        public ?CallbackQuery $callbackQuery = null,
    ) {}

    /**
     * Создаёт экземпляр из массива данных Telegram API.
     *
     * @param array<string, mixed> $data Данные от Telegram API.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            updateId: $data["update_id"],
            message: isset($data["message"])
                ? Message::fromArray($data["message"])
                : null,
            editedMessage: isset($data["edited_message"])
                ? Message::fromArray($data["edited_message"])
                : null,
            callbackQuery: isset($data["callback_query"])
                ? CallbackQuery::fromArray($data["callback_query"])
                : null,
        );
    }

    /**
     * Возвращает chat_id из любого типа обновления (message или callback_query).
     * Удобен для быстрого получения идентификатора чата без проверки типа обновления.
     */
    public function getChatId(): ?int
    {
        return $this->message?->chat->id ??
            ($this->editedMessage?->chat->id ??
                $this->callbackQuery?->message?->chat->id);
    }

    /**
     * Проверяет, является ли обновление текстовым сообщением.
     */
    public function isMessage(): bool
    {
        return $this->message !== null;
    }

    /**
     * Проверяет, является ли обновление callback-запросом.
     */
    public function isCallbackQuery(): bool
    {
        return $this->callbackQuery !== null;
    }

    /**
     * Проверяет, является ли обновление отредактированным сообщением.
     */
    public function isEditedMessage(): bool
    {
        return $this->editedMessage !== null;
    }
}
