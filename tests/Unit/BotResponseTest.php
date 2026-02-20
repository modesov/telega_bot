<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Tests\Unit;

use Modes\TelegaBot\BotResponse;
use PHPUnit\Framework\TestCase;

final class BotResponseTest extends TestCase
{
    public function test_creates_successful_response_with_array_result(): void
    {
        $response = new BotResponse(ok: true, result: ['message_id' => 1]);

        $this->assertTrue($response->ok);
        $this->assertSame(['message_id' => 1], $response->result);
    }

    public function test_creates_failed_response(): void
    {
        $response = new BotResponse(ok: false, result: null);

        $this->assertFalse($response->ok);
        $this->assertNull($response->result);
    }

    public function test_creates_response_with_bool_result(): void
    {
        $response = new BotResponse(ok: true, result: true);

        $this->assertTrue($response->ok);
        $this->assertTrue($response->result);
    }

    public function test_creates_response_with_false_result(): void
    {
        $response = new BotResponse(ok: true, result: false);

        $this->assertTrue($response->ok);
        $this->assertFalse($response->result);
    }

    public function test_creates_response_with_null_result(): void
    {
        $response = new BotResponse(ok: true, result: null);

        $this->assertTrue($response->ok);
        $this->assertNull($response->result);
    }

    public function test_properties_are_readonly(): void
    {
        $response = new BotResponse(ok: true, result: []);

        $reflection = new \ReflectionClass($response);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property \${$property->getName()} should be readonly",
            );
        }
    }

    public function test_creates_response_with_empty_array_result(): void
    {
        $response = new BotResponse(ok: true, result: []);

        $this->assertTrue($response->ok);
        $this->assertSame([], $response->result);
    }

    public function test_creates_response_with_nested_array_result(): void
    {
        $data = [
            'update_id' => 123,
            'message' => [
                'message_id' => 456,
                'chat' => ['id' => 789, 'type' => 'private'],
            ],
        ];

        $response = new BotResponse(ok: true, result: $data);

        $this->assertTrue($response->ok);
        $this->assertSame(123, $response->result['update_id']);
        $this->assertSame(456, $response->result['message']['message_id']);
    }
}
