<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет файл-документ (любой файл, не являющийся фото, видео и т.д.).
 *
 * @see https://core.telegram.org/bots/api#document
 */
final readonly class Document
{
    /**
     * @param string        $fileId       Идентификатор файла, используемый для скачивания или повторной отправки.
     * @param string        $fileUniqueId Уникальный идентификатор файла, неизменный со временем.
     * @param PhotoSize|null $thumbnail   Миниатюра документа (если есть).
     * @param string|null   $fileName     Оригинальное имя файла.
     * @param string|null   $mimeType     MIME-тип файла.
     * @param int|null      $fileSize     Размер файла в байтах.
     */
    public function __construct(
        public string    $fileId,
        public string    $fileUniqueId,
        public ?PhotoSize $thumbnail = null,
        public ?string   $fileName = null,
        public ?string   $mimeType = null,
        public ?int      $fileSize = null,
    ) {}

    /**
     * Создаёт экземпляр из массива данных Telegram API.
     *
     * @param array<string, mixed> $data Данные от Telegram API.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileId:       $data['file_id'],
            fileUniqueId: $data['file_unique_id'],
            thumbnail:    isset($data['thumbnail']) ? PhotoSize::fromArray($data['thumbnail']) : null,
            fileName:     $data['file_name'] ?? null,
            mimeType:     $data['mime_type'] ?? null,
            fileSize:     $data['file_size'] ?? null,
        );
    }
}
