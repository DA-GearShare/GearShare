<?php
namespace App\Controller;

use App\Repository\EquipmentRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        EquipmentRepository          $repository,
        #[MapQueryParameter] string  $sort = 'name',
        #[MapQueryParameter] string  $order = 'asc',
        #[MapQueryParameter] ?string $status = null,
        #[MapQueryParameter] ?string $search = null,
        #[MapQueryParameter] int     $page = 1,
        #[MapQueryParameter] int     $pageSize = 20,
    ): JsonResponse
    {
        if (!in_array($sort, ['name', 'location', 'type', 'availability']) || !in_array($order, ['asc', 'desc'])) {
            throw new BadRequestHttpException('Ungültige Sortierung.');
        }

        $qb = $repository->createQueryBuilder('e')
            ->where('e.deletedAt IS NULL')
            ->orderBy('e.' . $sort, $order)
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere('e.name LIKE :search OR e.model LIKE :search')->setParameter('search', '%' . $search . '%');
        }

        $paginator = new Paginator($qb);
        $totalItems = count($paginator);

        $items = array_map(fn($e) => [
            'id' => $e->getId(),
            'name' => $e->getName(),
            'location' => $e->getLocation(),
            'type' => $e->getType(),
            'model' => $e->getModel(),
            'status' => $e->getStatus(),
            'created_at' => $e->getCreatedAt(),
            'deleted_at' => $e->getDeletedAt(),
        ], iterator_to_array($paginator));

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $totalItems,
                'totalPages' => (int)ceil($totalItems / $pageSize),
            ],
        ]);
    }
}
