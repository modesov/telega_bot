<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Contracts;

/**
 * Контракт для разметки клавиатуры.
 *
 * Реализуется всеми типами клавиатур:
 * - {@see \Modes\TelegaBot\Types\InlineKeyboardMarkup} — инлайн-кнопки под сообщением
 * - {@see \Modes\TelegaBot\Types\ReplyKeyboardMarkup} — кнопки вместо обычной клавиатуры
 */
interface MarkupInterface
{
    /**
     * Сериализует разметку клавиатуры в массив для отправки в Telegram API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
