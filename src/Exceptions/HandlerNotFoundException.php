<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Exceptions;

/**
 * Исключение, выбрасываемое когда ни один обработчик не подошёл для входящего обновления.
 */
class HandlerNotFoundException extends TelegaBotException
{
    public function __construct(int $updateId)
    {
        parent::__construct(
            message: sprintf('No handler found for update #%d', $updateId),
        );
    }
}
