<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

use Modes\TelegaBot\Contracts\MarkupInterface;

/**
 * Представляет кастомную reply-клавиатуру.
 *
 * Отображается вместо стандартной клавиатуры устройства и остаётся
 * до тех пор, пока не будет скрыта через ReplyKeyboardRemove.
 *
 * @see https://core.telegram.org/bots/api#replykeyboardmarkup
 */
final readonly class ReplyKeyboardMarkup implements MarkupInterface
{
    /**
     * @param KeyboardButton[][] $keyboard          Двумерный массив кнопок клавиатуры.
     * @param bool|null          $resizeKeyboard    Уменьшить клавиатуру под количество кнопок.
     * @param bool|null          $oneTimeKeyboard   Скрыть клавиатуру после нажатия кнопки.
     * @param string|null        $inputFieldPlaceholder Placeholder в поле ввода (1-64 символа).
     * @param bool|null          $selective         Показать клавиатуру только выбранным пользователям.
     * @param bool|null          $isPersistent      Показывать клавиатуру всегда, не сворачивая.
     */
    public function __construct(
        public array   $keyboard,
        public ?bool   $resizeKeyboard = true,
        public ?bool   $oneTimeKeyboard = null,
        public ?string $inputFieldPlaceholder = null,
        public ?bool   $selective = null,
        public ?bool   $isPersistent = null,
    ) {}

    /**
     * Создаёт клавиатуру с одной строкой кнопок.
     *
     * @param KeyboardButton ...$buttons Кнопки в одну строку.
     */
    public static function singleRow(KeyboardButton ...$buttons): self
    {
        return new self(keyboard: [$buttons]);
    }

    /**
     * Создаёт клавиатуру, где каждая кнопка на отдельной строке.
     *
     * @param KeyboardButton ...$buttons Кнопки, каждая на своей строке.
     */
    public static function column(KeyboardButton ...$buttons): self
    {
        return new self(
            keyboard: array_map(fn($button) => [$button], $buttons),
        );
    }

    /**
     * Создаёт одноразовую клавиатуру, которая скрывается после нажатия.
     *
     * @param KeyboardButton[][] $keyboard Кнопки клавиатуры.
     */
    public static function oneTime(array $keyboard): self
    {
        return new self(keyboard: $keyboard, oneTimeKeyboard: true);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $data = [
            'keyboard' => array_map(
                fn(array $row) => array_map(
                    fn(KeyboardButton $button) => $button->toArray(),
                    $row,
                ),
                $this->keyboard,
            ),
        ];

        if ($this->resizeKeyboard !== null) {
            $data['resize_keyboard'] = $this->resizeKeyboard;
        }

        if ($this->oneTimeKeyboard !== null) {
            $data['one_time_keyboard'] = $this->oneTimeKeyboard;
        }

        if ($this->inputFieldPlaceholder !== null) {
            $data['input_field_placeholder'] = $this->inputFieldPlaceholder;
        }

        if ($this->selective !== null) {
            $data['selective'] = $this->selective;
        }

        if ($this->isPersistent !== null) {
            $data['is_persistent'] = $this->isPersistent;
        }

        return $data;
    }
}
