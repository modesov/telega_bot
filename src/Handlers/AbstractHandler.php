<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Handlers;

use Modes\TelegaBot\Bot;
use Modes\TelegaBot\Types\Update;

/**
 * Базовый абстрактный класс для всех обработчиков обновлений.
 *
 * Каждый обработчик отвечает за два действия:
 * 1. Определить, подходит ли он для данного обновления — {@see supports()}.
 * 2. Обработать обновление — {@see handle()}.
 *
 * Создавайте файлы в директории handlers/ и возвращайте экземпляр обработчика:
 *
 * ```php
 * // handlers/StartHandler.php
 * return new class extends AbstractCommandHandler {
 *     protected function getCommand(): string { return '/start'; }
 *
 *     public function handle(Update $update, Bot $bot): void {
 *         $bot->send(new SendMessage($update->message->chat->id, 'Привет!'));
 *     }
 * };
 * ```
 */
abstract class AbstractHandler
{
    /**
     * Определяет, может ли данный обработчик обработать входящее обновление.
     *
     * @param Update $update Входящее обновление от Telegram.
     *
     * @return bool true — если обработчик берёт это обновление на себя.
     */
    abstract public function supports(Update $update): bool;

    /**
     * Обрабатывает входящее обновление.
     *
     * Вызывается только если {@see supports()} вернул true.
     *
     * @param Update $update Входящее обновление от Telegram.
     * @param Bot    $bot    Экземпляр бота для отправки ответов.
     */
    abstract public function handle(Update $update, Bot $bot): void;
}
