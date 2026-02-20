<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Exceptions;

/**
 * Исключение, выбрасываемое при ошибке ответа Telegram Bot API.
 *
 * Содержит код ошибки и описание от Telegram.
 */
class ApiException extends TelegaBotException
{
    /**
     * @param string $description Описание ошибки от Telegram API.
     * @param int    $errorCode   Код ошибки от Telegram API.
     */
    public function __construct(
        string $description,
        private readonly int $errorCode,
    ) {
        parent::__construct(
            message: sprintf('[%d] %s', $errorCode, $description),
            code: $errorCode,
        );
    }

    /**
     * Возвращает код ошибки Telegram API.
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Создаёт экземпляр исключения из массива ответа API.
     *
     * @param array $data Декодированный JSON-ответ от Telegram API.
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            description: $data['description'] ?? 'Unknown API error',
            errorCode: $data['error_code'] ?? 0,
        );
    }
}
