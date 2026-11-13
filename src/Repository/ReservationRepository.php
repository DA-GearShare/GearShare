<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * @param Uuid|null $userId
     * @param ReservationStatus|null $status
     * @param bool|null $overdue
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function findPaginated(?Uuid $userId, ?ReservationStatus $status, ?bool $overdue, int $page, int $pageSize): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.items', 'i')
            ->addSelect('i')
            ->orderBy('r.startDatetime', 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        if ($userId !== null) {
            $qb->leftJoin('r.requestedBy', 'rb')
                ->leftJoin('r.responsibleUser', 'ru')
                ->andWhere('rb.userId = :userId OR ru.userId = :userId')
                ->setParameter('userId', $userId, UuidType::NAME);
        }

        if ($status !== null) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        if ($overdue !== null) {
            // Overdue = endDatetime < now AND at least one item not yet returned
            $unreturned = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from(ReservationItem::class, 'ri')
                ->where('ri.reservation = r')
                ->andWhere('ri.status != :returned')
                ->getDQL();

            $isOverdue = $qb->expr()->andX(
                'r.endDatetime < :now',
                $qb->expr()->exists($unreturned),
            );

            $qb->andWhere($overdue ? $isOverdue : $qb->expr()->not($isOverdue))
                ->setParameter('now', new DateTimeImmutable())
                ->setParameter('returned', ReservationItemStatus::Returned);
        }

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'totalItems' => count($paginator),
        ];
    }
}
