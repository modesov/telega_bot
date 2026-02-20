<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Exceptions;

/**
 * Базовое исключение библиотеки.
 *
 * Все остальные исключения библиотеки наследуются от этого класса,
 * что позволяет перехватывать любую ошибку библиотеки одним catch-блоком.
 */
class TelegaBotException extends \RuntimeException
{
}
