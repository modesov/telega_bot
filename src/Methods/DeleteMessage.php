<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод deleteMessage — удаление сообщения из чата.
 *
 * Сообщение можно удалить только если оно было отправлено менее 48 часов назад.
 * Бот может удалять любые сообщения в группах, если он является администратором.
 *
 * @see https://core.telegram.org/bots/api#deletemessage
 */
readonly class DeleteMessage implements MethodsInterface
{
    /**
     * @param int|string $chatId    Идентификатор чата или username канала.
     * @param int        $messageId Идентификатор удаляемого сообщения.
     */
    public function __construct(
        private int|string $chatId,
        private int $messageId,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "deleteMessage";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        return [
            "chat_id"    => $this->chatId,
            "message_id" => $this->messageId,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * При успехе Telegram возвращает True (булево значение) вместо объекта сообщения.
     */
    public function getResponse(array $data): BotResponse
    {
        return new BotResponse(
            ok: $data["ok"],
            result: $data["result"] ?? null,
        );
    }
}
