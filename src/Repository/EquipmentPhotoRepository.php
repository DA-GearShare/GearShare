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
     * Hauptbild zuerst, dann nach Upload-Zeitpunkt.
     *
     * @return list<EquipmentPhoto>
     */
    public function findForEquipment(Equipment $equipment): array
    {
        return $this->findBy(
            ['equipment' => $equipment],
            ['isMain' => 'DESC', 'uploadedAt' => 'ASC'],
        );
    }

    /**
     * Legt ein Foto an. Es wird Hauptbild, wenn das gewünscht ist
     * oder das Gerät noch keines hat.
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

    public function updateIsMain(EquipmentPhoto $photo, Equipment $equipment, bool $isMain): void
    {
        if ($isMain) {
            $equipment->setMainPhoto($photo);
        } else {
            $photo->setIsMain(false);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Entfernt das Foto aus der DB (orphanRemoval löscht die Zeile).
     * War es das Hauptbild, wird das älteste verbleibende Foto zum Hauptbild.
     * Die Datei im Bucket löscht der Aufrufer, erst nach diesem Aufruf.
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
