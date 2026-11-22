<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Equipment;
use App\Entity\EquipmentPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<EquipmentPhoto>
 */
class EquipmentPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentPhoto::class);
    }

    /**
     * Main Image first, then by upload-timing.
     */
    public function findForEquipment(Equipment $equipment): array
    {
        return $this->findBy(
            ['equipment' => $equipment],
            ['isMain' => 'DESC', 'uploadedAt' => 'ASC'],
        );
    }

    /**
     * Create photo. Will be main image if requested or if first image.
     */
    public function createForEquipment(Equipment $equipment, Uuid $id, string $url, bool $isMain): EquipmentPhoto
    {
        $photo = new EquipmentPhoto($id, $equipment, $url);

        if ($isMain || $equipment->getMainPhoto() === null) {
            $equipment->setMainPhoto($photo);
        }

        $em = $this->getEntityManager();
        $em->persist($photo);
        $em->flush();

        return $photo;
    }

    public function markAsIsMain(EquipmentPhoto $photo, Equipment $equipment): void
    {
        $equipment->setMainPhoto($photo);
        $this->getEntityManager()->flush();
    }

    /**
     * Deletes the image from DB
     * If main image, changes oldest image to new main image
     * File in bucket will be deleted after this call.
     */
    public function removeFromEquipment(EquipmentPhoto $photo, Equipment $equipment): void
    {
        $wasMain = $photo->isMain();

        $equipment->removePhoto($photo);

        if ($wasMain) {
            $remaining = $equipment->getPhotos()->toArray();
            usort($remaining, static fn (EquipmentPhoto $a, EquipmentPhoto $b) => $a->getUploadedAt() <=> $b->getUploadedAt());
            $equipment->setMainPhoto($remaining[0] ?? null);
        }

        $this->getEntityManager()->flush();
    }
}
