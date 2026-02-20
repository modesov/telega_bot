<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод getMe — получение базовой информации о боте.
 *
 * Возвращает объект User с информацией о текущем боте.
 * Удобен для проверки корректности токена и получения username бота.
 *
 * @see https://core.telegram.org/bots/api#getme
 */
readonly class GetMe implements MethodsInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "getMe";
    }

    /**
     * {@inheritdoc}
     *
     * Метод не принимает никаких параметров.
     */
    public function getRequestParams(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * При успехе возвращает объект User, представляющий бота.
     */
    public function getResponse(array $data): BotResponse
    {
        return new BotResponse(
            ok: $data["ok"],
            result: $data["result"] ?? null,
        );
    }
}
