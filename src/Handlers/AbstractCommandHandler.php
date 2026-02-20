<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Handlers;

use Modes\TelegaBot\Types\Update;

/**
 * Абстрактный обработчик команд Telegram-бота.
 *
 * Команда — это сообщение, начинающееся с символа '/' (например, /start, /help).
 * Поддерживает команды с аргументами: '/start arg1 arg2'.
 *
 * Пример использования:
 *
 * ```php
 * // handlers/StartHandler.php
 * return new class extends AbstractCommandHandler {
 *     protected function getCommand(): string
 *     {
 *         return '/start';
 *     }
 *
 *     public function handle(Update $update, Bot $bot): void
 *     {
 *         $bot->send(new SendMessage(
 *             $update->message->chat->id,
 *             'Добро пожаловать! Я бот.',
 *         ));
 *     }
 * };
 * ```
 */
abstract class AbstractCommandHandler extends AbstractHandler
{
    /**
     * Возвращает команду, которую обрабатывает данный обработчик.
     *
     * Должна начинаться с '/' и содержать только буквы, цифры и подчёркивания.
     *
     * @return string Например: '/start', '/help', '/settings'.
     */
    abstract protected function getCommand(): string;

    /**
     * {@inheritdoc}
     *
     * Возвращает true если сообщение начинается с команды, возвращаемой {@see getCommand()}.
     * Учитывает команды вида '/start@BotName' (с упоминанием бота в группах).
     */
    public function supports(Update $update): bool
    {
        $text = $update->message?->text;

        if ($text === null) {
            return false;
        }

        $command = $this->getCommand();

        // Точное совпадение: '/start'
        if ($text === $command) {
            return true;
        }

        // Команда с аргументами: '/start arg1 arg2'
        if (str_starts_with($text, $command . ' ')) {
            return true;
        }

        // Команда с упоминанием бота в группах: '/start@MyBot'
        if (str_starts_with($text, $command . '@')) {
            return true;
        }

        return false;
    }

    /**
     * Возвращает аргументы команды в виде массива строк.
     *
     * Например, для сообщения '/start foo bar' вернёт ['foo', 'bar'].
     * Если аргументов нет — возвращает пустой массив.
     *
     * @param Update $update Входящее обновление.
     *
     * @return string[]
     */
    protected function getArguments(Update $update): array
    {
        $text = $update->message?->text ?? '';
        $parts = explode(' ', $text, 2);

        if (count($parts) < 2 || trim($parts[1]) === '') {
            return [];
        }

        return explode(' ', trim($parts[1]));
    }

    /**
     * Возвращает аргументы команды в виде одной строки.
     *
     * Например, для сообщения '/echo Hello World' вернёт 'Hello World'.
     * Если аргументов нет — возвращает пустую строку.
     *
     * @param Update $update Входящее обновление.
     */
    protected function getArgumentsString(Update $update): string
    {
        $text = $update->message?->text ?? '';
        $parts = explode(' ', $text, 2);

        return isset($parts[1]) ? trim($parts[1]) : '';
    }
}
