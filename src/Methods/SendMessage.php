<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MarkupInterface;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод sendMessage — отправка текстового сообщения.
 *
 * Поддерживает форматирование текста (Markdown/HTML),
 * инлайн- и reply-клавиатуры.
 *
 * @see https://core.telegram.org/bots/api#sendmessage
 */
readonly class SendMessage implements MethodsInterface
{
    /**
     * @param int|string          $chatId                Идентификатор чата или username канала.
     * @param string              $text                  Текст сообщения (1–4096 символов).
     * @param string|null         $parseMode             Режим форматирования: 'Markdown', 'MarkdownV2' или 'HTML'.
     * @param MarkupInterface|null $replyMarkup          Клавиатура или другая разметка для сообщения.
     * @param int|null            $replyToMessageId      ID сообщения, на которое является ответом данное.
     * @param bool|null           $disableWebPagePreview Отключить предпросмотр ссылок в сообщении.
     * @param bool|null           $disableNotification   Отправить сообщение без звукового уведомления.
     */
    public function __construct(
        private int|string $chatId,
        private string $text,
        private ?string $parseMode = null,
        private ?MarkupInterface $replyMarkup = null,
        private ?int $replyToMessageId = null,
        private ?bool $disableWebPagePreview = null,
        private ?bool $disableNotification = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "sendMessage";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $params = [
            "chat_id" => $this->chatId,
            "text" => $this->text,
        ];

        if ($this->parseMode !== null) {
            $params["parse_mode"] = $this->parseMode;
        }

        if ($this->replyMarkup !== null) {
            $params["reply_markup"] = $this->replyMarkup->toArray();
        }

        if ($this->replyToMessageId !== null) {
            $params["reply_to_message_id"] = $this->replyToMessageId;
        }

        if ($this->disableWebPagePreview !== null) {
            $params["disable_web_page_preview"] = $this->disableWebPagePreview;
        }

        if ($this->disableNotification !== null) {
            $params["disable_notification"] = $this->disableNotification;
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
