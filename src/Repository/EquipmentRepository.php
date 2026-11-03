<?php

namespace App\Repository;

use App\Dto\EquipmentCreateDto;
use App\Dto\EquipmentUpdateDto;
use App\Entity\Equipment;
use App\Entity\EquipmentPool;
use App\Enum\EquipmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\ReservationItem;
use DateTimeImmutable;

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
    public function findPaginated(string $sort, string $order, ?DateTimeImmutable $from, ?DateTimeImmutable $to, ?string $search, int $page, int $pageSize ): array{
        $qb = $this->createQueryBuilder('e')
            ->where('e.deletedAt IS NULL')
            ->orderBy('e.' . $sort, $order)
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        if ($from !== null && $to !== null) {
            $reserved = $this->getEntityManager()->createQueryBuilder()
                ->select('ri.id')
                ->from(ReservationItem::class, 'ri')
                ->innerJoin('ri.reservation', 'r')
                ->where('ri.requestedEquipment = e OR ri.assignedEquipment = e')
                ->andWhere('r.startDatetime < :to')
                ->andWhere('r.endDatetime > :from');

            $qb->andWhere($qb->expr()->not($qb->expr()->exists($reserved->getDQL())))
                ->setParameter('from', $from)
                ->setParameter('to', $to);
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

    /** Prüft auch soft-gelöschte Geräte, weil `code` in der DB unique ist. */
    public function codeExists(string $code): bool
    {
        return $this->count(['code' => $code]) > 0;
    }

    /**
     * Wie codeExists(), ignoriert aber das übergebene Gerät selbst (für Updates).
     * Prüft ebenfalls soft-gelöschte Geräte.
     */
    public function codeExistsForOther(string $code, Equipment $self): bool
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.code = :code')
            ->andWhere('e.id != :id')
            ->setParameter('code', $code)
            ->setParameter('id', $self->getId(), UuidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * Nächster freier automatischer Code im Format der Fixtures: EQ-0001, EQ-0002, ...
     * Händisch vergebene Codes in anderen Formaten werden ignoriert.
     */
    public function nextGeneratedCode(): string
    {
        $codes = $this->createQueryBuilder('e')
            ->select('e.code')
            ->where('e.code LIKE :prefix')
            ->setParameter('prefix', 'EQ-%')
            ->getQuery()
            ->getSingleColumnResult();

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^EQ-(\d+)$/', (string)$code, $m)) {
                $max = max($max, (int)$m[1]);
            }
        }

        return sprintf('EQ-%04d', $max + 1);
    }

    public function createFromDto(EquipmentCreateDto $dto): Equipment|Response
    {
        $em = $this->getEntityManager();

        $pool = null;
        if ($dto->poolId !== null) {
            $pool = $em->find(EquipmentPool::class, $dto->poolId);
            if ($pool === null) {
                return new JsonResponse([
                    'message' => 'Invalider Request Body',
                    'errors' => ['poolId' => 'Pool not found'],
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($dto->code !== null && $this->codeExists($dto->code)) {
            return new Response(
                sprintf('Code "%s" ist bereits vergeben.', $dto->code),
                Response::HTTP_CONFLICT
            );
        }

        $equipment = new Equipment()
            ->setName($dto->name)
            ->setLocation($dto->location)
            ->setType($dto->type)
            ->setModel($dto->model)
            ->setCode($dto->code ?? $this->nextGeneratedCode())
            ->setStatus(EquipmentStatus::Available)
            ->setPool($pool);

        $em->persist($equipment);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return new Response(
                'Code ist bereits vergeben, bitte erneut versuchen.',
                Response::HTTP_CONFLICT
            );
        }

        return $equipment;
    }

    /**
     * Aktualisiert Stammdaten eines Geräts (PUT). Status und Pool bleiben unverändert.
     * Ist kein `code` angegeben, bleibt der bisherige Code erhalten.
     */
    public function updateFromDto(Equipment $equipment, EquipmentUpdateDto $dto): Equipment|Response
    {
        $em = $this->getEntityManager();

        $codeChanged = $dto->code !== null && $dto->code !== $equipment->getCode();
        if ($codeChanged && $this->codeExistsForOther($dto->code, $equipment)) {
            return new Response(
                sprintf('Code "%s" ist bereits vergeben.', $dto->code),
                Response::HTTP_CONFLICT
            );
        }

        $equipment
            ->setName($dto->name)
            ->setLocation($dto->location)
            ->setType($dto->type)
            ->setModel($dto->model);

        if ($codeChanged) {
            $equipment->setCode($dto->code);
        }

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return new Response(
                'Code ist bereits vergeben, bitte erneut versuchen.',
                Response::HTTP_CONFLICT
            );
        }

        return $equipment;
    }
}
