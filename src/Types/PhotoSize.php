<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Types;

/**
 * Представляет одно фото или миниатюру файла определённого размера.
 *
 * @see https://core.telegram.org/bots/api#photosize
 */
final readonly class PhotoSize
{
    /**
     * @param string   $fileId       Идентификатор файла, используемый для скачивания или повторной отправки.
     * @param string   $fileUniqueId Уникальный идентификатор файла, неизменный со временем.
     * @param int      $width        Ширина фото в пикселях.
     * @param int      $height       Высота фото в пикселях.
     * @param int|null $fileSize     Размер файла в байтах (если известен).
     */
    public function __construct(
        public string $fileId,
        public string $fileUniqueId,
        public int    $width,
        public int    $height,
        public ?int   $fileSize = null,
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
            width:        $data['width'],
            height:       $data['height'],
            fileSize:     $data['file_size'] ?? null,
        );
    }
}
