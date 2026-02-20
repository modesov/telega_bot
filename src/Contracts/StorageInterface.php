<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Contracts;

/**
 * Контракт для хранилища состояния бота.
 *
 * Позволяет сохранять и читать произвольные значения между запросами.
 * Реализации могут использовать файлы, Redis, базу данных и т.д.
 */
interface StorageInterface
{
    /**
     * Возвращает значение по ключу.
     *
     * @param string $key     Ключ записи.
     * @param mixed  $default Значение по умолчанию, если ключ не найден.
     *
     * @return mixed Сохранённое значение или $default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Сохраняет значение по ключу.
     *
     * @param string $key   Ключ записи.
     * @param mixed  $value Значение для сохранения.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Проверяет существование ключа в хранилище.
     *
     * @param string $key Ключ записи.
     */
    public function has(string $key): bool;

    /**
     * Удаляет запись по ключу.
     *
     * @param string $key Ключ записи.
     */
    public function delete(string $key): void;

    /**
     * Очищает всё хранилище.
     */
    public function clear(): void;
}
