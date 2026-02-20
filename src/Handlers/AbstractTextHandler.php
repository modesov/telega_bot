<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Handlers;

use Modes\TelegaBot\Types\Update;

/**
 * Абстрактный обработчик текстовых сообщений.
 *
 * Позволяет реагировать на произвольный текст сообщения:
 * точное совпадение, совпадение по регулярному выражению или любое сообщение.
 *
 * Пример — точное совпадение текста:
 *
 * ```php
 * // handlers/HelpTextHandler.php
 * return new class extends AbstractTextHandler {
 *     protected function getText(): string
 *     {
 *         return 'Помощь';
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         $bot->send(new SendMessage($update->message->chat->id, 'Чем могу помочь?'));
 *     }
 * };
 * ```
 *
 * Пример — совпадение по регулярному выражению:
 *
 * ```php
 * // handlers/PhoneHandler.php
 * return new class extends AbstractTextHandler {
 *     protected function getText(): string
 *     {
 *         return '/^\+?[0-9]{10,15}$/';
 *     }
 *
 *     protected function matchByRegex(): bool
 *     {
 *         return true;
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         $bot->send(new SendMessage(
 *             $update->message->chat->id,
 *             'Вы отправили номер телефона: ' . $update->message->text,
 *         ));
 *     }
 * };
 * ```
 *
 * Пример — обработчик любого текстового сообщения (fallback):
 *
 * ```php
 * // handlers/FallbackHandler.php
 * return new class extends AbstractTextHandler {
 *     protected function getText(): string
 *     {
 *         return '';
 *     }
 *
 *     protected function matchAny(): bool
 *     {
 *         return true;
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         $bot->send(new SendMessage(
 *             $update->message->chat->id,
 *             'Я не понимаю эту команду. Напишите /help.',
 *         ));
 *     }
 * };
 * ```
 */
abstract class AbstractTextHandler extends AbstractHandler
{
    /**
     * Возвращает текст (или регулярное выражение) для сопоставления.
     *
     * - При {@see matchAny()} = true — возвращаемое значение игнорируется.
     * - При {@see matchByRegex()} = true — должно быть валидным PCRE-паттерном (со слэшами).
     * - В остальных случаях — строка для точного сравнения (регистрозависимо).
     *
     * @return string Текст или регулярное выражение.
     */
    abstract protected function getText(): string;

    /**
     * Если возвращает true — обработчик срабатывает на ЛЮБОЕ текстовое сообщение.
     *
     * Полезно для реализации fallback-обработчика.
     * По умолчанию — false.
     */
    protected function matchAny(): bool
    {
        return false;
    }

    /**
     * Если возвращает true — сопоставление идёт по регулярному выражению (PCRE).
     *
     * Паттерн должен включать ограничители, например: '/^\d+$/'.
     * По умолчанию — false (точное совпадение строк).
     */
    protected function matchByRegex(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Возвращает true если:
     * - обновление содержит текстовое сообщение,
     * - И выполняется одно из условий: matchAny(), точное совпадение или совпадение по regex.
     */
    public function supports(Update $update): bool
    {
        $text = $update->message?->text;

        if ($text === null) {
            return false;
        }

        if ($this->matchAny()) {
            return true;
        }

        if ($this->matchByRegex()) {
            return (bool) preg_match($this->getText(), $text);
        }

        return $text === $this->getText();
    }

    /**
     * Возвращает именованные или пронумерованные группы из regex-совпадения.
     *
     * Полезно при {@see matchByRegex()} = true для извлечения данных из сообщения.
     * Если совпадений нет или используется не regex-режим — возвращает пустой массив.
     *
     * @param Update $update Входящее обновление.
     *
     * @return string[] Массив совпадений (индексированный или ассоциативный).
     */
    protected function getMatches(Update $update): array
    {
        $text = $update->message?->text ?? '';

        if (!$this->matchByRegex()) {
            return [];
        }

        preg_match($this->getText(), $text, $matches);

        return $matches;
    }
}
