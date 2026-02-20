<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

readonly class User
{
    public int $id;
    public bool $isBot;
    public string $firstName;
    public ?string $lastName;
    public ?string $username;
    public ?string $languageCode;
    public ?bool $isPremium;
    public ?bool $addedToAttachmentMenu;
    public ?bool $canJoinGroups;
    public ?bool $canReadAllGroupMessages;
    public ?bool $supportsInlineQueries;
    public ?bool $canConnectToBusiness;
    public ?bool $hasMainWebApp;
    public ?bool $hasTopicsEnabled;
    public ?bool $allowsUsersToCreateTopics;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->isBot = $data['is_bot'];
        $this->firstName = $data['first_name'];
        $this->lastName = $data['last_name'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->languageCode = $data['language_code'] ?? null;
        $this->isPremium = $data['is_premium'] ?? null;
        $this->addedToAttachmentMenu = $data['added_to_attachment_menu'] ?? null;
        $this->canJoinGroups = $data['can_join_groups'] ?? null;
        $this->canReadAllGroupMessages = $data['can_read_all_group_messages'] ?? null;
        $this->supportsInlineQueries = $data['supports_inline_queries'] ?? null;
        $this->canConnectToBusiness = $data['can_connect_to_business'] ?? null;
        $this->hasMainWebApp = $data['has_main_web_app'] ?? null;
        $this->hasTopicsEnabled = $data['has_topics_enabled'] ?? null;
        $this->allowsUsersToCreateTopics = $data['allows_user_to_create_topics'] ?? null;
    }
}
