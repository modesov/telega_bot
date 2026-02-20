<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет одну кнопку инлайн-клавиатуры.
 *
 * @see https://core.telegram.org/bots/api#inlinekeyboardbutton
 */
final readonly class InlineKeyboardButton
{
    /**
     * @param string      $text         Текст на кнопке.
     * @param string|null $callbackData Данные, отправляемые в callback_query при нажатии (до 64 байт).
     * @param string|null $url          HTTP-ссылка, открываемая при нажатии кнопки.
     * @param string|null $switchInlineQuery            Переключает пользователя в режим инлайн-запроса.
     * @param string|null $switchInlineQueryCurrentChat Переключает в режим инлайн-запроса в текущем чате.
     * @param bool|null   $pay          Отображать кнопку оплаты.
     */
    public function __construct(
        public string  $text,
        public ?string $callbackData = null,
        public ?string $url = null,
        public ?string $switchInlineQuery = null,
        public ?string $switchInlineQueryCurrentChat = null,
        public ?bool   $pay = null,
    ) {}

    /**
     * Создаёт кнопку с callback_data.
     *
     * @param string $text         Текст кнопки.
     * @param string $callbackData Данные для callback_query.
     */
    public static function callbackButton(string $text, string $callbackData): self
    {
        return new self(text: $text, callbackData: $callbackData);
    }

    /**
     * Создаёт кнопку-ссылку.
     *
     * @param string $text Текст кнопки.
     * @param string $url  URL для открытия.
     */
    public static function urlButton(string $text, string $url): self
    {
        return new self(text: $text, url: $url);
    }

    /**
     * Сериализует кнопку в массив для Telegram API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['text' => $this->text];

        if ($this->callbackData !== null) {
            $data['callback_data'] = $this->callbackData;
        }

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->switchInlineQuery !== null) {
            $data['switch_inline_query'] = $this->switchInlineQuery;
        }

        if ($this->switchInlineQueryCurrentChat !== null) {
            $data['switch_inline_query_current_chat'] = $this->switchInlineQueryCurrentChat;
        }

        if ($this->pay !== null) {
            $data['pay'] = $this->pay;
        }

        return $data;
    }
}
