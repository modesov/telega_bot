<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MarkupInterface;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод sendPhoto — отправка фотографии.
 *
 * Поддерживает отправку по file_id, URL или загрузку нового файла.
 * Также поддерживает подпись, форматирование и клавиатуры.
 *
 * @see https://core.telegram.org/bots/api#sendphoto
 */
readonly class SendPhoto implements MethodsInterface
{
    /**
     * @param int|string           $chatId              Идентификатор чата или username канала.
     * @param string               $photo               file_id уже загруженного фото, URL или путь к файлу.
     * @param string|null          $caption             Подпись к фото (0–1024 символа).
     * @param string|null          $parseMode           Режим форматирования подписи: 'Markdown', 'MarkdownV2' или 'HTML'.
     * @param MarkupInterface|null $replyMarkup         Клавиатура или другая разметка для сообщения.
     * @param int|null             $replyToMessageId    ID сообщения, на которое является ответом данное.
     * @param bool|null            $disableNotification Отправить сообщение без звукового уведомления.
     * @param bool|null            $hasSpoiler          Скрыть фото под спойлером.
     */
    public function __construct(
        private int|string $chatId,
        private string $photo,
        private ?string $caption = null,
        private ?string $parseMode = null,
        private ?MarkupInterface $replyMarkup = null,
        private ?int $replyToMessageId = null,
        private ?bool $disableNotification = null,
        private ?bool $hasSpoiler = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "sendPhoto";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $params = [
            "chat_id" => $this->chatId,
            "photo"   => $this->photo,
        ];

        if ($this->caption !== null) {
            $params["caption"] = $this->caption;
        }

        if ($this->parseMode !== null) {
            $params["parse_mode"] = $this->parseMode;
        }

        if ($this->replyMarkup !== null) {
            $params["reply_markup"] = $this->replyMarkup->toArray();
        }

        if ($this->replyToMessageId !== null) {
            $params["reply_to_message_id"] = $this->replyToMessageId;
        }

        if ($this->disableNotification !== null) {
            $params["disable_notification"] = $this->disableNotification;
        }

        if ($this->hasSpoiler !== null) {
            $params["has_spoiler"] = $this->hasSpoiler;
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $data): BotResponse
    {
        return new BotResponse(
            ok: $data["ok"],
            result: $data["result"] ?? null,
        );
    }
}
