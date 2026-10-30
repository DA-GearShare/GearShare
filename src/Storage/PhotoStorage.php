<?php

declare(strict_types=1);

namespace App\Storage;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Uid\Uuid;

final class PhotoStorage
{
    private const array ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    // Named Autowiring: equipment.storage -> $equipmentStorage, condition.storage -> $conditionStorage
    public function __construct(
        private readonly FilesystemOperator $equipmentStorage,
        private readonly FilesystemOperator $conditionStorage,
    ) {
    }

    /**
     * @return string der erkannte MIME-Type
     *
     * @throws FilesystemException
     */
    public function putEquipment(UploadedFile $file, Uuid $id): string
    {
        return $this->put($this->equipmentStorage, $file, $id);
    }

    /**
     * @return string der erkannte MIME-Type
     *
     * @throws FilesystemException
     */
    public function putCondition(UploadedFile $file, Uuid $id): string
    {
        return $this->put($this->conditionStorage, $file, $id);
    }

    /**
     * @throws FilesystemException
     */
    public function deleteEquipment(Uuid $id): void
    {
        $this->equipmentStorage->delete($id->toRfc4122());
    }

    /**
     * @throws FilesystemException
     */
    public function deleteCondition(Uuid $id): void
    {
        $this->conditionStorage->delete($id->toRfc4122());
    }

    /**
     * @throws FilesystemException wenn S3 nicht erreichbar ist o. Ä. Bewusst NICHT
     *         abfangen: sonst legt der Controller einen DB-Eintrag für ein Bild an,
     *         das es im Bucket gar nicht gibt.
     */
    private function put(FilesystemOperator $fs, UploadedFile $file, Uuid $id): string
    {
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new UnsupportedMediaTypeHttpException('Nur JPEG, PNG oder WebP.');
        }

        $stream = fopen($file->getPathname(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Hochgeladene Datei konnte nicht gelesen werden.');
        }

        try {
            // Key = nur die UUID, passend zur Regex in media.conf
            $fs->writeStream($id->toRfc4122(), $stream, ['ContentType' => $mime]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $mime;
    }
}
