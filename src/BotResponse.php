<?php

namespace Modes\TelegaBot;

readonly class BotResponse
{
    public function __construct(
        public bool $ok,
        public array|bool|null $result,
    ) {}
}
