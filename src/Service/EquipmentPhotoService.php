<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\EquipmentPhoto;
use App\Repository\EquipmentPhotoRepository;
use App\Storage\PhotoStorage;
use Psr\Log\LoggerInterface;
use Throwable;

final class EquipmentPhotoService
{
    public function __construct(
        private readonly EquipmentPhotoRepository $photoRepository,
        private readonly PhotoStorage             $storage,
        private readonly LoggerInterface          $logger,
    )
    {
    }

    /**
     * Removes the photo from the DB and afterwards from the bucket.
     *
     * @param EquipmentPhoto $photo
     * @param Equipment $equipment
     * @return void
     */
    public function delete(EquipmentPhoto $photo, Equipment $equipment): void
    {
        $id = $photo->getId();

        // First DB, then bucket: if bucket fails, an unreferenced file proceeds to exist
        $this->photoRepository->removeFromEquipment($photo, $equipment);

        try {
            $this->storage->deleteEquipment($id);
        } catch (Throwable $e) {
            $this->logger->error('Equipment-Foto konnte nicht aus dem Bucket gelöscht werden.', [
                'photoId' => $id->toRfc4122(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Deletes all photos of the given equipment.
     *
     * @param Equipment $equipment
     * @return void
     */
    public function deleteAllFor(Equipment $equipment): void
    {
        foreach ($this->photoRepository->findForEquipment($equipment) as $photo) {
            $this->delete($photo, $equipment);
        }
    }
}
