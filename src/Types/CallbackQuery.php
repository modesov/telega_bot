<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет входящий callback-запрос от инлайн-кнопки.
 *
 * Когда пользователь нажимает на инлайн-кнопку с callback_data,
 * боту приходит объект CallbackQuery. Необходимо ответить на него
 * методом answerCallbackQuery, иначе кнопка будет "висеть" в состоянии загрузки.
 *
 * @see https://core.telegram.org/bots/api#callbackquery
 */
final readonly class CallbackQuery
{
    /**
     * @param string       $id              Уникальный идентификатор запроса.
     * @param User         $from            Пользователь, нажавший кнопку.
     * @param string|null  $data            Данные, переданные в callback_data кнопки (до 64 байт).
     * @param Message|null $message         Сообщение, к которому прикреплена кнопка (может отсутствовать).
     * @param string|null  $inlineMessageId Идентификатор инлайн-сообщения (если кнопка была в инлайн-сообщении).
     * @param string|null  $chatInstance    Глобальный идентификатор чата, из которого был открыт инлайн-запрос.
     * @param string|null  $gameShortName   Краткое имя игры (если нажата игровая кнопка).
     */
    public function __construct(
        public string $id,
        public User $from,
        public ?string $data = null,
        public ?Message $message = null,
        public ?string $inlineMessageId = null,
        public ?string $chatInstance = null,
        public ?string $gameShortName = null,
    ) {}

    /**
     * Создаёт экземпляр из массива данных Telegram API.
     *
     * @param array<string, mixed> $data Данные от Telegram API.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data["id"],
            from: new User($data["from"]),
            data: $data["data"] ?? null,
            message: isset($data["message"])
                ? Message::fromArray($data["message"])
                : null,
            inlineMessageId: $data["inline_message_id"] ?? null,
            chatInstance: $data["chat_instance"] ?? null,
            gameShortName: $data["game_short_name"] ?? null,
        );
    }
}
