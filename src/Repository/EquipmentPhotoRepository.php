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
     * Main Image first, then by upload-timing
     *
     * @param Equipment $equipment
     * @return array
     */
    public function findForEquipment(Equipment $equipment): array
    {
        return $this->findBy(
            ['equipment' => $equipment],
            ['isMain' => 'DESC', 'uploadedAt' => 'ASC'],
        );
    }

    /**
     * Create photo. Will be main image if requested or if first image
     *
     * @param Equipment $equipment
     * @param Uuid $id
     * @param string $url
     * @param bool $isMain
     * @return EquipmentPhoto
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

    /**
     * @param EquipmentPhoto $photo
     * @param Equipment $equipment
     * @return void
     */
    public function markAsIsMain(EquipmentPhoto $photo, Equipment $equipment): void
    {
        $equipment->setMainPhoto($photo);
        $this->getEntityManager()->flush();
    }

    /**
     * Deletes the image from DB
     * If main image, changes oldest image to new main image
     * File in bucket will be deleted after this call
     *
     * @param EquipmentPhoto $photo
     * @param Equipment $equipment
     * @return void
     */
    public function removeFromEquipment(EquipmentPhoto $photo, Equipment $equipment): void
    {
        $wasMain = $photo->isMain();

        $equipment->removePhoto($photo);

        if ($wasMain) {
            $remaining = $equipment->getPhotos()->toArray();
            usort($remaining, fn(EquipmentPhoto $a, EquipmentPhoto $b) => $a->getUploadedAt() <=> $b->getUploadedAt());
            $equipment->setMainPhoto($remaining[0] ?? null);
        }

        $this->getEntityManager()->flush();
    }
}
