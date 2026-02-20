<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

use Modes\TelegaBot\Contracts\MarkupInterface;

/**
 * Представляет инлайн-клавиатуру, прикреплённую к сообщению.
 *
 * @see https://core.telegram.org/bots/api#inlinekeyboardmarkup
 */
final readonly class InlineKeyboardMarkup implements MarkupInterface
{
    /**
     * @param InlineKeyboardButton[][] $inlineKeyboard Двумерный массив кнопок.
     *                                                  Каждый вложенный массив — строка кнопок.
     */
    public function __construct(
        public array $inlineKeyboard,
    ) {}

    /**
     * Создаёт клавиатуру с одной строкой кнопок.
     *
     * @param InlineKeyboardButton ...$buttons Кнопки в одну строку.
     */
    public static function singleRow(InlineKeyboardButton ...$buttons): self
    {
        return new self(inlineKeyboard: [$buttons]);
    }

    /**
     * Создаёт клавиатуру, где каждая кнопка на отдельной строке.
     *
     * @param InlineKeyboardButton ...$buttons Кнопки, каждая на своей строке.
     */
    public static function column(InlineKeyboardButton ...$buttons): self
    {
        return new self(
            inlineKeyboard: array_map(fn($button) => [$button], $buttons),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'inline_keyboard' => array_map(
                fn(array $row) => array_map(
                    fn(InlineKeyboardButton $button) => $button->toArray(),
                    $row,
                ),
                $this->inlineKeyboard,
            ),
        ];
    }
}
