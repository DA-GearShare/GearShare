<?php

namespace App\Repository;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Equipment>
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    /**
     * @return array{items: Equipment[], totalItems: int}
     */
    public function findPaginated(string $sort, string $order, ?string $status, ?string $search, int $page, int $pageSize ): array{
        $qb = $this->createQueryBuilder('e')
            ->where('e.deletedAt IS NULL')
            ->orderBy('e.' . $sort, $order)
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);
        if ($status){
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        if ($search){
            $qb->andWhere('e.name LIKE :search OR e.model LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        $paginator = new Paginator($qb);
        return [
            'items' => iterator_to_array($paginator),
            'totalItems' => count($paginator),
        ];
    }

//    /**
//     * @return Equipment[] Returns an array of Equipment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Equipment
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
