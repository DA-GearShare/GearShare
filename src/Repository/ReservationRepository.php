<?php

namespace App\Repository;

use App\Dto\ReservationCreateDto;
use App\Entity\Equipment;
use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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

    /**
     * Legt eine Reservierungsanfrage an
     *
     * Verfügbarkeit: Ein Gerät ist belegt, wenn es in einer überlappenden,
     * noch offenen Reservierung tatsächlich (assigned) bzw. angefragt (requested)
     * vorkommt.
     *
     * Die beteiligten Geräte werden für die Dauer der Prüfung gesperrt, damit
     * parallele Anfragen nicht beide durchrutschen.
     *
     * Pools: später -> auskommentierte Logik (Markierung "Pools: später")
     */
    public function createFromDto(ReservationCreateDto $dto, User $user): Reservation|Response
    {
        return $this->getEntityManager()->wrapInTransaction(
            fn(EntityManagerInterface $em): Reservation|Response => $this->createLocked($em, $dto, $user)
        );
    }

    /**
     * @param EntityManagerInterface $em
     * @param ReservationCreateDto $dto
     * @param User $user
     * @return Reservation|Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createLocked(EntityManagerInterface $em, ReservationCreateDto $dto, User $user): Reservation|Response
    {
        // ---- Existenz prüfen + sperren (sortiert, um Deadlocks zu vermeiden) ----
        $notFound = [];

        $equipmentIds = $dto->equipmentIds;
        sort($equipmentIds);
        /** @var array<string, Equipment> $equipment */
        $equipment = [];
        foreach ($equipmentIds as $id) {
            $e = $em->find(Equipment::class, $id, LockMode::PESSIMISTIC_WRITE);
            if ($e === null || $e->isDeleted()) {
                $notFound[sprintf('equipmentIds[%d]', array_search($id, $dto->equipmentIds, true))] = 'Gerät nicht gefunden';
                continue;
            }
            $equipment[$id] = $e;
        }

        if ($notFound !== []) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $notFound,
            ], Response::HTTP_BAD_REQUEST);
        }

        $start = $dto->startDatetime;
        $end = $dto->endDatetime;

        $unavailableEquipment = $equipment === [] ? [] : $this->findBusyEquipmentIds(array_values($equipment), $start, $end);

        if ($unavailableEquipment !== []) {
            return new JsonResponse([
                'code' => 'NOT_AVAILABLE',
                'message' => 'Mindestens eine angefragte Position ist im gewünschten Zeitraum nicht verfügbar.',
                'details' => [
                    'equipmentIds' => $unavailableEquipment,
                    // Pools: später
                ],
            ], Response::HTTP_CONFLICT);
        }

        $reservation = new Reservation()
            ->setRequestedBy($user)
            ->setResponsibleUser($user)
            ->setStatus(ReservationStatus::Requested)
            ->setStartDatetime($start)
            ->setEndDatetime($end);

        foreach ($dto->equipmentIds as $id) {
            $reservation->addItem(new ReservationItem()
                ->setRequestedEquipment($equipment[$id])
                ->setStatus(ReservationItemStatus::Requested));
        }

        $em->persist($reservation);
        $em->flush();

        return $reservation;
    }

    /**
     * Positionen, die im Zeitraum ein Gerät blockieren: überlappend, Reservierung
     * nicht abgelehnt/storniert/abgeschlossen, Position nicht zurückgegeben.
     */
    private function blockingItemsQuery(DateTimeImmutable $start, DateTimeImmutable $end): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->from(ReservationItem::class, 'ri')
            ->innerJoin('ri.reservation', 'r')
            ->andWhere('r.startDatetime < :end')
            ->andWhere('r.endDatetime > :start')
            ->andWhere('r.status NOT IN (:closedStatuses)')
            ->andWhere('ri.status != :returned')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('closedStatuses', [
                ReservationStatus::Denied->value,
                ReservationStatus::Cancelled->value,
                ReservationStatus::Returned->value,
            ])
            ->setParameter('returned', ReservationItemStatus::Returned);
    }

    /**
     * @param list<Equipment> $equipment
     * @return list<string>
     */
    private function findBusyEquipmentIds(array $equipment, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $rows = $this->blockingItemsQuery($start, $end)
            ->select('IDENTITY(ri.requestedEquipment) AS requested')
            ->andWhere('ri.requestedEquipment IN (:equipment)')
            ->setParameter('equipment', $equipment)
            ->getQuery()
            ->getScalarResult();

        $busy = [];
        foreach ($rows as $row) {
            $busy[] = strtolower((string) ($row['requested']));
        }

        return array_values(array_unique($busy));
    }

    /**
     * @return mixed
     */
    public function findOverdue(): mixed
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.items', 'i')
            ->addSelect('i')
            ->orderBy('r.startDatetime', 'DESC')
            ->addOrderBy('r.id', 'ASC');

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

            $qb->andWhere($isOverdue)
                ->setParameter('now', new DateTimeImmutable())
                ->setParameter('returned', ReservationItemStatus::Returned);

        return $qb->getQuery()->getResult();
    }
}
