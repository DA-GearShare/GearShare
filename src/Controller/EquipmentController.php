<?php
namespace App\Controller;


use App\Repository\EquipmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        EquipmentRepository $repository,
        #[MapQueryParameter] string $sort = 'name',
        #[MapQueryParameter] string $order = 'asc',
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $pageSize = 20,
    ): JsonResponse
    {
        $equipment = $repository->findBy(['deletedAt' => null], [$sort => $order], $pageSize, ($page - 1) * $pageSize);

        $items = array_map(fn ($e) => [
            'id' => $e->getId(),
            'name' => $e->getName(),
            'location' => $e->getLocation(),
            'type' => $e->getType(),
            'model' => $e->getModel(),
            'status' => $e->getStatus(),
        ], $equipment);

        $totalItems = $repository->count(['deletedAt' => null]);
        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $totalItems,
                'totalPages' => (int) ceil($totalItems / $pageSize),
            ]
        ]);
    }
}
