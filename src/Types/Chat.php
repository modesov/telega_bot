<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

readonly class Chat
{
    public int $id;
    public string $type;
    public ?string $title;
    public ?string $username;
    public ?string $firstName;
    public ?string $lastName;
    public ?bool $isForum;
    public ?bool $isDirectMessages;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->title = $data['title'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->firstName = $data['first_name'] ?? null;
        $this->lastName = $data['last_name'] ?? null;
        $this->isForum = $data['is_forum'] ?? null;
        $this->isDirectMessages = $data['is_direct_messages'] ?? null;
    }
}
