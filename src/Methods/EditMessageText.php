<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MarkupInterface;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод editMessageText — редактирование текста отправленного сообщения.
 *
 * Используется для изменения текста обычного сообщения или инлайн-сообщения.
 * Для обычных сообщений необходимо передать chat_id и message_id.
 * Для инлайн-сообщений — inline_message_id.
 *
 * @see https://core.telegram.org/bots/api#editmessagetext
 */
readonly class EditMessageText implements MethodsInterface
{
    /**
     * @param string               $text                  Новый текст сообщения (1–4096 символов).
     * @param int|string|null      $chatId                Идентификатор чата. Обязателен при редактировании обычного сообщения.
     * @param int|null             $messageId             Идентификатор редактируемого сообщения. Обязателен при редактировании обычного сообщения.
     * @param string|null          $inlineMessageId       Идентификатор инлайн-сообщения. Обязателен при редактировании инлайн-сообщения.
     * @param string|null          $parseMode             Режим форматирования текста: 'Markdown', 'MarkdownV2' или 'HTML'.
     * @param MarkupInterface|null $replyMarkup           Новая инлайн-клавиатура для сообщения.
     * @param bool|null            $disableWebPagePreview Отключить предпросмотр ссылок в сообщении.
     */
    public function __construct(
        private string $text,
        private int|string|null $chatId = null,
        private ?int $messageId = null,
        private ?string $inlineMessageId = null,
        private ?string $parseMode = null,
        private ?MarkupInterface $replyMarkup = null,
        private ?bool $disableWebPagePreview = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "editMessageText";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $params = [
            "text" => $this->text,
        ];

        if ($this->chatId !== null) {
            $params["chat_id"] = $this->chatId;
        }

        if ($this->messageId !== null) {
            $params["message_id"] = $this->messageId;
        }

        if ($this->inlineMessageId !== null) {
            $params["inline_message_id"] = $this->inlineMessageId;
        }

        if ($this->parseMode !== null) {
            $params["parse_mode"] = $this->parseMode;
        }

        if ($this->replyMarkup !== null) {
            $params["reply_markup"] = $this->replyMarkup->toArray();
        }

        if ($this->disableWebPagePreview !== null) {
            $params["disable_web_page_preview"] = $this->disableWebPagePreview;
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
