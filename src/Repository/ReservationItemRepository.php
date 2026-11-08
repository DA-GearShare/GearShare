<?php

namespace App\Repository;

use App\Entity\ReservationItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Equipment;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<ReservationItem>
 */
class ReservationItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationItem::class);
    }

    /**
     * @param Equipment[] $equipment
     * @return string[]
     */
    public function findReservedEquipmentIds(array $equipment, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($equipment === []) {
            return [];
        }

        $items = $this->createQueryBuilder('ri')
            ->innerJoin('ri.reservation', 'r')
            ->where('ri.requestedEquipment IN (:equipment) OR ri.assignedEquipment IN (:equipment)')
            ->andWhere('r.startDatetime < :to')
            ->andWhere('r.endDatetime > :from')
            ->setParameter('equipment', $equipment)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        $ids = [];
        foreach ($items as $item) {
            if ($item->getRequestedEquipment() !== null) {
                $ids[] = $item->getRequestedEquipment()->getId()->toRfc4122();
            }
            if ($item->getAssignedEquipment() !== null) {
                $ids[] = $item->getAssignedEquipment()->getId()->toRfc4122();
            }
        }

        return $ids;
    }


//    /**
//     * @return ReservationItem[] Returns an array of ReservationItem objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ReservationItem
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
