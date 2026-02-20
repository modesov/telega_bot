<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Tests\Unit\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\StorageInterface;
use Modes\TelegaBot\Methods\GetUpdates;
use Modes\TelegaBot\Types\Update;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetUpdatesTest extends TestCase
{
    private StorageInterface&MockObject $storage;
    private GetUpdates $method;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->method  = new GetUpdates($this->storage);
    }

    // -------------------------------------------------------------------------
    // getMethod
    // -------------------------------------------------------------------------

    public function test_get_method_returns_correct_api_method_name(): void
    {
        $this->assertSame('getUpdates', $this->method->getMethod());
    }

    // -------------------------------------------------------------------------
    // getRequestParams — offset logic
    // -------------------------------------------------------------------------

    public function test_get_request_params_offset_is_zero_when_no_previous_update(): void
    {
        $this->storage
            ->method('get')
            ->with('last_update_id', 0)
            ->willReturn(0);

        $params = $this->method->getRequestParams();

        $this->assertArrayHasKey('offset', $params);
        $this->assertSame(0, $params['offset']);
    }

    public function test_get_request_params_offset_is_last_update_id_plus_one(): void
    {
        $this->storage
            ->method('get')
            ->with('last_update_id', 0)
            ->willReturn(100);

        $params = $this->method->getRequestParams();

        $this->assertSame(101, $params['offset']);
    }

    public function test_get_request_params_includes_limit(): void
    {
        $this->storage->method('get')->willReturn(0);

        $method = new GetUpdates($this->storage, limit: 50);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('limit', $params);
        $this->assertSame(50, $params['limit']);
    }

    public function test_get_request_params_includes_timeout(): void
    {
        $this->storage->method('get')->willReturn(0);

        $method = new GetUpdates($this->storage, timeout: 30);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('timeout', $params);
        $this->assertSame(30, $params['timeout']);
    }

    public function test_get_request_params_default_limit_is_100(): void
    {
        $this->storage->method('get')->willReturn(0);

        $params = $this->method->getRequestParams();

        $this->assertSame(100, $params['limit']);
    }

    public function test_get_request_params_default_timeout_is_zero(): void
    {
        $this->storage->method('get')->willReturn(0);

        $params = $this->method->getRequestParams();

        $this->assertSame(0, $params['timeout']);
    }

    // -------------------------------------------------------------------------
    // getResponse — empty result
    // -------------------------------------------------------------------------

    public function test_get_response_returns_empty_result_when_ok_false(): void
    {
        $this->storage->expects($this->never())->method('set');

        $response = $this->method->getResponse([
            'ok'     => false,
            'result' => [],
        ]);

        $this->assertInstanceOf(BotResponse::class, $response);
        $this->assertFalse($response->ok);
        $this->assertSame([], $response->result);
    }

    public function test_get_response_returns_empty_result_when_result_is_empty_array(): void
    {
        $this->storage->expects($this->never())->method('set');

        $response = $this->method->getResponse([
            'ok'     => true,
            'result' => [],
        ]);

        $this->assertInstanceOf(BotResponse::class, $response);
        $this->assertTrue($response->ok);
        $this->assertSame([], $response->result);
    }

    // -------------------------------------------------------------------------
    // getResponse — with updates
    // -------------------------------------------------------------------------

    public function test_get_response_parses_updates_into_update_objects(): void
    {
        $this->storage->method('set');

        $data = [
            'ok'     => true,
            'result' => [
                $this->makeUpdateData(update_id: 10),
                $this->makeUpdateData(update_id: 11),
                $this->makeUpdateData(update_id: 12),
            ],
        ];

        $response = $this->method->getResponse($data);

        $this->assertTrue($response->ok);
        $this->assertIsArray($response->result);
        $this->assertCount(3, $response->result);

        foreach ($response->result as $update) {
            $this->assertInstanceOf(Update::class, $update);
        }
    }

    public function test_get_response_saves_last_update_id_to_storage(): void
    {
        $this->storage
            ->expects($this->once())
            ->method('set')
            ->with('last_update_id', 15);

        $data = [
            'ok'     => true,
            'result' => [
                $this->makeUpdateData(update_id: 13),
                $this->makeUpdateData(update_id: 14),
                $this->makeUpdateData(update_id: 15),
            ],
        ];

        $this->method->getResponse($data);
    }

    public function test_get_response_saves_correct_update_id_for_single_update(): void
    {
        $this->storage
            ->expects($this->once())
            ->method('set')
            ->with('last_update_id', 42);

        $this->method->getResponse([
            'ok'     => true,
            'result' => [
                $this->makeUpdateData(update_id: 42),
            ],
        ]);
    }

    public function test_get_response_update_ids_match_input_data(): void
    {
        $this->storage->method('set');

        $response = $this->method->getResponse([
            'ok'     => true,
            'result' => [
                $this->makeUpdateData(update_id: 100),
                $this->makeUpdateData(update_id: 101),
            ],
        ]);

        $this->assertSame(100, $response->result[0]->updateId);
        $this->assertSame(101, $response->result[1]->updateId);
    }

    public function test_get_response_does_not_save_to_storage_when_result_empty(): void
    {
        $this->storage->expects($this->never())->method('set');

        $this->method->getResponse([
            'ok'     => true,
            'result' => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUpdateData(int $update_id): array
    {
        return [
            'update_id' => $update_id,
            'message'   => [
                'message_id' => $update_id * 10,
                'date'       => 1700000000,
                'text'       => 'Hello',
                'chat'       => [
                    'id'   => 999,
                    'type' => 'private',
                ],
                'from' => [
                    'id'         => 1,
                    'is_bot'     => false,
                    'first_name' => 'Test',
                ],
            ],
        ];
    }
}
