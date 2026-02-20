<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Storage;

use Modes\TelegaBot\Contracts\StorageInterface;

/**
 * Файловое хранилище состояния бота.
 *
 * Сохраняет данные в JSON-файл на диске. Подходит для одиночных процессов
 * (polling). Для продакшн-окружений рекомендуется реализовать
 * {@see StorageInterface} на базе Redis или базы данных.
 */
final class FileStorage implements StorageInterface
{
    /**
     * Данные, загруженные из файла.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param string $filePath Путь к JSON-файлу хранилища.
     */
    public function __construct(private readonly string $filePath = '/tmp/telega_bot_storage.json')
    {
        $this->load();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): void
    {
        unset($this->data[$key]);
        $this->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->data = [];
        $this->persist();
    }

    /**
     * Загружает данные из файла в память.
     */
    private function load(): void
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        $contents = file_get_contents($this->filePath);

        if ($contents === false || $contents === '') {
            return;
        }

        $decoded = json_decode($contents, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->data = $decoded;
        }
    }

    /**
     * Сохраняет текущее состояние данных в файл.
     */
    private function persist(): void
    {
        file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX,
        );
    }
}
