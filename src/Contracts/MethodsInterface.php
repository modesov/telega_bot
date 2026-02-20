<?php

namespace Modes\TelegaBot\Contracts;

use Modes\TelegaBot\BotResponse;

interface MethodsInterface
{
    public function getMethod(): string;

    public function getRequestParams(): array;

    public function getResponse(array $data): BotResponse;
}
