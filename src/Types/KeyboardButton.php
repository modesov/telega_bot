<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет одну кнопку reply-клавиатуры.
 *
 * @see https://core.telegram.org/bots/api#keyboardbutton
 */
final readonly class KeyboardButton
{
    /**
     * @param string    $text            Текст кнопки. Будет отправлен как сообщение при нажатии.
     * @param bool|null $requestContact  Если true — при нажатии запрашивает контакт пользователя.
     * @param bool|null $requestLocation Если true — при нажатии запрашивает геолокацию пользователя.
     */
    public function __construct(
        public string $text,
        public ?bool  $requestContact = null,
        public ?bool  $requestLocation = null,
    ) {}

    /**
     * Создаёт обычную текстовую кнопку.
     *
     * @param string $text Текст кнопки.
     */
    public static function make(string $text): self
    {
        return new self(text: $text);
    }

    /**
     * Создаёт кнопку, запрашивающую контакт пользователя.
     *
     * @param string $text Текст кнопки.
     */
    public static function requestContact(string $text): self
    {
        return new self(text: $text, requestContact: true);
    }

    /**
     * Создаёт кнопку, запрашивающую геолокацию пользователя.
     *
     * @param string $text Текст кнопки.
     */
    public static function requestLocation(string $text): self
    {
        return new self(text: $text, requestLocation: true);
    }

    /**
     * Сериализует кнопку в массив для Telegram API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['text' => $this->text];

        if ($this->requestContact !== null) {
            $data['request_contact'] = $this->requestContact;
        }

        if ($this->requestLocation !== null) {
            $data['request_location'] = $this->requestLocation;
        }

        return $data;
    }
}
