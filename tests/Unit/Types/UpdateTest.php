<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Tests\Unit\Types;

use Modes\TelegaBot\Types\CallbackQuery;
use Modes\TelegaBot\Types\Chat;
use Modes\TelegaBot\Types\Message;
use Modes\TelegaBot\Types\Update;
use Modes\TelegaBot\Types\User;
use PHPUnit\Framework\TestCase;

final class UpdateTest extends TestCase
{
    private function makeMessageData(int $chatId = 100, string $text = 'Hello'): array
    {
        return [
            'message_id' => 1,
            'date'       => 1700000000,
            'text'       => $text,
            'chat'       => [
                'id'   => $chatId,
                'type' => 'private',
            ],
            'from' => [
                'id'         => 42,
                'is_bot'     => false,
                'first_name' => 'John',
            ],
        ];
    }

    private function makeCallbackQueryData(string $data = 'action'): array
    {
        return [
            'id'            => 'cq_123',
            'chat_instance' => 'ci_456',
            'data'          => $data,
            'from'          => [
                'id'         => 42,
                'is_bot'     => false,
                'first_name' => 'John',
            ],
            'message' => $this->makeMessageData(),
        ];
    }

    // -------------------------------------------------------------------------
    // fromArray — message update
    // -------------------------------------------------------------------------

    public function test_from_array_with_message(): void
    {
        $data = [
            'update_id' => 123,
            'message'   => $this->makeMessageData(),
        ];

        $update = Update::fromArray($data);

        $this->assertSame(123, $update->updateId);
        $this->assertInstanceOf(Message::class, $update->message);
        $this->assertNull($update->editedMessage);
        $this->assertNull($update->callbackQuery);
    }

    public function test_from_array_with_edited_message(): void
    {
        $data = [
            'update_id'      => 456,
            'edited_message' => $this->makeMessageData(200, 'edited text'),
        ];

        $update = Update::fromArray($data);

        $this->assertSame(456, $update->updateId);
        $this->assertNull($update->message);
        $this->assertInstanceOf(Message::class, $update->editedMessage);
        $this->assertSame('edited text', $update->editedMessage->text);
        $this->assertNull($update->callbackQuery);
    }

    public function test_from_array_with_callback_query(): void
    {
        $data = [
            'update_id'      => 789,
            'callback_query' => $this->makeCallbackQueryData('approve'),
        ];

        $update = Update::fromArray($data);

        $this->assertSame(789, $update->updateId);
        $this->assertNull($update->message);
        $this->assertNull($update->editedMessage);
        $this->assertInstanceOf(CallbackQuery::class, $update->callbackQuery);
        $this->assertSame('approve', $update->callbackQuery->data);
    }

    public function test_from_array_with_no_payload(): void
    {
        $data = ['update_id' => 1];

        $update = Update::fromArray($data);

        $this->assertSame(1, $update->updateId);
        $this->assertNull($update->message);
        $this->assertNull($update->editedMessage);
        $this->assertNull($update->callbackQuery);
    }

    // -------------------------------------------------------------------------
    // isMessage / isCallbackQuery / isEditedMessage
    // -------------------------------------------------------------------------

    public function test_is_message_returns_true_when_message_present(): void
    {
        $update = Update::fromArray([
            'update_id' => 1,
            'message'   => $this->makeMessageData(),
        ]);

        $this->assertTrue($update->isMessage());
        $this->assertFalse($update->isCallbackQuery());
        $this->assertFalse($update->isEditedMessage());
    }

    public function test_is_callback_query_returns_true_when_callback_present(): void
    {
        $update = Update::fromArray([
            'update_id'      => 1,
            'callback_query' => $this->makeCallbackQueryData(),
        ]);

        $this->assertFalse($update->isMessage());
        $this->assertTrue($update->isCallbackQuery());
        $this->assertFalse($update->isEditedMessage());
    }

    public function test_is_edited_message_returns_true_when_edited_message_present(): void
    {
        $update = Update::fromArray([
            'update_id'      => 1,
            'edited_message' => $this->makeMessageData(),
        ]);

        $this->assertFalse($update->isMessage());
        $this->assertFalse($update->isCallbackQuery());
        $this->assertTrue($update->isEditedMessage());
    }

    public function test_all_flags_false_when_no_payload(): void
    {
        $update = Update::fromArray(['update_id' => 1]);

        $this->assertFalse($update->isMessage());
        $this->assertFalse($update->isCallbackQuery());
        $this->assertFalse($update->isEditedMessage());
    }

    // -------------------------------------------------------------------------
    // getChatId
    // -------------------------------------------------------------------------

    public function test_get_chat_id_from_message(): void
    {
        $update = Update::fromArray([
            'update_id' => 1,
            'message'   => $this->makeMessageData(chatId: 555),
        ]);

        $this->assertSame(555, $update->getChatId());
    }

    public function test_get_chat_id_from_edited_message(): void
    {
        $update = Update::fromArray([
            'update_id'      => 1,
            'edited_message' => $this->makeMessageData(chatId: 777),
        ]);

        $this->assertSame(777, $update->getChatId());
    }

    public function test_get_chat_id_from_callback_query(): void
    {
        $callbackData = $this->makeCallbackQueryData();
        $callbackData['message']['chat']['id'] = 999;

        $update = Update::fromArray([
            'update_id'      => 1,
            'callback_query' => $callbackData,
        ]);

        $this->assertSame(999, $update->getChatId());
    }

    public function test_get_chat_id_returns_null_when_no_payload(): void
    {
        $update = Update::fromArray(['update_id' => 1]);

        $this->assertNull($update->getChatId());
    }

    // -------------------------------------------------------------------------
    // Readonly properties
    // -------------------------------------------------------------------------

    public function test_properties_are_readonly(): void
    {
        $update = Update::fromArray(['update_id' => 1]);

        $reflection = new \ReflectionClass($update);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property \${$property->getName()} should be readonly",
            );
        }
    }

    // -------------------------------------------------------------------------
    // Nested objects integrity
    // -------------------------------------------------------------------------

    public function test_message_chat_is_parsed_correctly(): void
    {
        $update = Update::fromArray([
            'update_id' => 1,
            'message'   => $this->makeMessageData(chatId: 321),
        ]);

        $this->assertInstanceOf(Chat::class, $update->message->chat);
        $this->assertSame(321, $update->message->chat->id);
        $this->assertSame('private', $update->message->chat->type);
    }

    public function test_callback_query_from_user_is_parsed_correctly(): void
    {
        $update = Update::fromArray([
            'update_id'      => 1,
            'callback_query' => $this->makeCallbackQueryData(),
        ]);

        $this->assertInstanceOf(User::class, $update->callbackQuery->from);
        $this->assertSame(42, $update->callbackQuery->from->id);
        $this->assertSame('John', $update->callbackQuery->from->firstName);
    }
}
