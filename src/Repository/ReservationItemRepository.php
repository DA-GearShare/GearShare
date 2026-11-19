<?php

namespace App\Repository;

use App\Entity\ReservationItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Equipment;
use DateTimeImmutable;
use App\Enum\ReservationStatus;

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
        $items = $this->getReservationItemsQB($equipment, $from, $to)
            ->getQuery()
            ->getResult();

        $ids = [];
        foreach ($items as $item) {
            if ($item->getRequestedEquipment() !== null) {
                $ids[] = $item->getRequestedEquipment()->getId()->toRfc4122();
            }
        }

        return $ids;
    }
    public function findInPeriod(array $equipment, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($equipment === []) {
            return [];
        }
        return $this->getReservationItemsQB($equipment, $from, $to)
            ->addSelect('r')
            ->andWhere('r.status NOT IN (:inactive)')
            ->setParameter('inactive', [ReservationStatus::Denied, ReservationStatus::Cancelled, ReservationStatus::Returned])
            ->orderBy('r.startDatetime', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function getReservationItemsQB(array $equipment, DateTimeImmutable $from, DateTimeImmutable $to): QueryBuilder
    {
        return $this->createQueryBuilder('ri')
            ->innerJoin('ri.reservation', 'r')
            ->where('ri.requestedEquipment IN (:equipment)')
            ->andWhere('r.endDatetime > :from')
            ->andWhere('r.startDatetime < :to')
            ->setParameter('equipment', $equipment)
            ->setParameter('from', $from)
            ->setParameter('to', $to);
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
