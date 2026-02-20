<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет входящее сообщение.
 *
 * @see https://core.telegram.org/bots/api#message
 */
final readonly class Message
{
    /**
     * @param int           $messageId      Уникальный идентификатор сообщения.
     * @param Chat          $chat           Чат, в котором было отправлено сообщение.
     * @param int           $date           Дата отправки сообщения (Unix timestamp).
     * @param string|null   $text           Текст сообщения (для текстовых сообщений).
     * @param User|null     $from           Отправитель сообщения (отсутствует для сообщений от каналов).
     * @param Message|null  $replyToMessage Сообщение, на которое является ответом данное сообщение.
     * @param PhotoSize[]|null $photo       Массив объектов PhotoSize (доступных размеров фото).
     * @param Document|null $document       Документ, прикреплённый к сообщению.
     * @param string|null   $caption        Подпись к фото, документу и т.д.
     */
    public function __construct(
        public int $messageId,
        public Chat $chat,
        public int $date,
        public ?string $text = null,
        public ?User $from = null,
        public ?Message $replyToMessage = null,
        public ?array $photo = null,
        public ?Document $document = null,
        public ?string $caption = null,
    ) {}

    /**
     * Создаёт экземпляр из массива данных Telegram API.
     *
     * @param array<string, mixed> $data Данные от Telegram API.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data["message_id"],
            chat: new Chat($data["chat"]),
            date: $data["date"],
            text: $data["text"] ?? null,
            from: isset($data["from"]) ? new User($data["from"]) : null,
            replyToMessage: isset($data["reply_to_message"])
                ? self::fromArray($data["reply_to_message"])
                : null,
            photo: isset($data["photo"])
                ? array_map(
                    fn(array $p) => PhotoSize::fromArray($p),
                    $data["photo"],
                )
                : null,
            document: isset($data["document"])
                ? Document::fromArray($data["document"])
                : null,
            caption: $data["caption"] ?? null,
        );
    }

    /**
     * Возвращает true, если сообщение содержит фото.
     */
    public function hasPhoto(): bool
    {
        return !empty($this->photo);
    }

    /**
     * Возвращает true, если сообщение содержит документ.
     */
    public function hasDocument(): bool
    {
        return $this->document !== null;
    }

    /**
     * Возвращает наибольшее по размеру фото из массива photo.
     * Telegram всегда присылает несколько размеров; последний — самый большой.
     */
    public function getBestPhoto(): ?PhotoSize
    {
        if (empty($this->photo)) {
            return null;
        }

        return end($this->photo) ?: null;
    }
}
