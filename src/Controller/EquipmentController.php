<?php
namespace App\Controller;

use App\Repository\EquipmentRepository;
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
        if (!in_array($sort, ['name', 'location', 'type']) || !in_array($order, ['asc', 'desc'])) {
            return new Response('Ungültige Sortierung.', Response::HTTP_XXX);
        }
        if ($page < 1 || $pageSize < 1) {
            return new Response('Ungültige Paginierung.', Response::HTTP_XXX);
        }
        if ($status && !in_array($status, ['available', 'reserved', 'issued'])){
            return new Response('Ungültiger Status.', Response::HTTP_XXX);
        }

        $result = $repository->findPaginated($sort, $order, $status, $search, $page, $pageSize);

        $items = array_map(fn($e) => [
            'id' => $e->getId(),
            'name' => $e->getName(),
            'location' => $e->getLocation(),
            'type' => $e->getType(),
            'model' => $e->getModel(),
            'status' => $e->getStatus(),
            'created_at' => $e->getCreatedAt(),
            'deleted_at' => $e->getDeletedAt(),
        ], $result['items']);

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $result['totalItems'],
                'totalPages' => (int) ceil($result['totalItems'] / $pageSize),
            ],
        ]);
    }
}
