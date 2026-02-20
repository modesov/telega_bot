<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Tests\Unit\Storage;

use Modes\TelegaBot\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

final class FileStorageTest extends TestCase
{
    private string $filePath;
    private FileStorage $storage;

    protected function setUp(): void
    {
        $this->filePath = sys_get_temp_dir() . '/telega_bot_test_' . uniqid() . '.json';
        $this->storage  = new FileStorage($this->filePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertNull($this->storage->get('missing_key'));
        $this->assertSame(42, $this->storage->get('missing_key', 42));
        $this->assertSame('default', $this->storage->get('missing_key', 'default'));
    }

    public function test_set_and_get_string_value(): void
    {
        $this->storage->set('name', 'Alice');

        $this->assertSame('Alice', $this->storage->get('name'));
    }

    public function test_set_and_get_integer_value(): void
    {
        $this->storage->set('last_update_id', 12345);

        $this->assertSame(12345, $this->storage->get('last_update_id'));
    }

    public function test_set_and_get_boolean_value(): void
    {
        $this->storage->set('is_active', true);

        $this->assertTrue($this->storage->get('is_active'));
    }

    public function test_set_and_get_array_value(): void
    {
        $data = ['foo' => 'bar', 'baz' => 42];
        $this->storage->set('config', $data);

        $this->assertSame($data, $this->storage->get('config'));
    }

    public function test_set_overwrites_existing_value(): void
    {
        $this->storage->set('counter', 1);
        $this->storage->set('counter', 2);

        $this->assertSame(2, $this->storage->get('counter'));
    }

    public function test_has_returns_false_for_missing_key(): void
    {
        $this->assertFalse($this->storage->has('nonexistent'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        $this->storage->set('existing', 'value');

        $this->assertTrue($this->storage->has('existing'));
    }

    public function test_has_returns_true_for_null_value(): void
    {
        $this->storage->set('nullable', null);

        $this->assertTrue($this->storage->has('nullable'));
    }

    public function test_delete_removes_key(): void
    {
        $this->storage->set('to_delete', 'value');
        $this->storage->delete('to_delete');

        $this->assertFalse($this->storage->has('to_delete'));
        $this->assertNull($this->storage->get('to_delete'));
    }

    public function test_delete_non_existing_key_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $this->storage->delete('nonexistent');
    }

    public function test_clear_removes_all_keys(): void
    {
        $this->storage->set('key1', 'val1');
        $this->storage->set('key2', 'val2');
        $this->storage->set('key3', 'val3');

        $this->storage->clear();

        $this->assertFalse($this->storage->has('key1'));
        $this->assertFalse($this->storage->has('key2'));
        $this->assertFalse($this->storage->has('key3'));
    }

    public function test_data_is_persisted_to_file(): void
    {
        $this->storage->set('persisted_key', 'persisted_value');

        $this->assertFileExists($this->filePath);

        $contents = file_get_contents($this->filePath);
        $decoded  = json_decode($contents, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('persisted_key', $decoded);
        $this->assertSame('persisted_value', $decoded['persisted_key']);
    }

    public function test_data_is_loaded_from_existing_file(): void
    {
        // Сохраняем данные через первый экземпляр
        $this->storage->set('shared_key', 'shared_value');

        // Создаём новый экземпляр, указывающий на тот же файл
        $anotherStorage = new FileStorage($this->filePath);

        $this->assertSame('shared_value', $anotherStorage->get('shared_key'));
    }

    public function test_multiple_keys_persist_independently(): void
    {
        $this->storage->set('a', 1);
        $this->storage->set('b', 2);
        $this->storage->set('c', 3);

        $this->assertSame(1, $this->storage->get('a'));
        $this->assertSame(2, $this->storage->get('b'));
        $this->assertSame(3, $this->storage->get('c'));
    }

    public function test_file_created_only_after_first_write(): void
    {
        $freshPath    = sys_get_temp_dir() . '/telega_fresh_' . uniqid() . '.json';
        $freshStorage = new FileStorage($freshPath);

        // До первой записи файл не должен существовать
        $this->assertFileDoesNotExist($freshPath);

        $freshStorage->set('key', 'value');

        $this->assertFileExists($freshPath);

        unlink($freshPath);
    }

    public function test_zero_is_stored_and_retrieved_correctly(): void
    {
        $this->storage->set('offset', 0);

        $this->assertSame(0, $this->storage->get('offset'));
        $this->assertTrue($this->storage->has('offset'));
    }
}
