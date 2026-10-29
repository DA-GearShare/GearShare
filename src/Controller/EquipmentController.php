<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EquipmentCreateDto;
use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    private const string UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
    private const int MAX_PHOTO_BYTES = 15 * 1024 * 1024;

    public function __construct(
        #[Autowire('%env(MEDIA_BASE_URL)%')] private readonly string $mediaBaseUrl,
    )
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        EquipmentRepository          $repository,
        #[MapQueryParameter] string  $sort = 'name',
        #[MapQueryParameter] string  $order = 'asc',
        #[MapQueryParameter] ?string $status = null,
        #[MapQueryParameter] ?string $search = null,
        #[MapQueryParameter] int     $page = 1,
        #[MapQueryParameter] int     $pageSize = 20,
    ): Response
    {
        if (!in_array($sort, ['name', 'location', 'type']) || !in_array($order, ['asc', 'desc'])) {
            return new Response('Ungültige Sortierung.', Response::HTTP_BAD_REQUEST);
        }
        if ($page < 1 || $pageSize < 1) {
            return new Response('Ungültige Paginierung.', Response::HTTP_BAD_REQUEST);
        }
        if ($status && !in_array($status, ['available', 'reserved', 'issued'])){
            return new Response('Ungültiger Status.', Response::HTTP_BAD_REQUEST);
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request             $request,
        EquipmentRepository $equipmentRepository,
    ): Response
    {
        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new Response('Body muss ein JSON Objekt sein', Response::HTTP_BAD_REQUEST);
        }

        $dto = EquipmentCreateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $equipment = $equipmentRepository->createFromDto($dto);
        if ($equipment instanceof Response) {
            return $equipment;
        }

        return $this->json($this->serializeEquipment($equipment), Response::HTTP_CREATED, [
            'Location' => '/api/v1/equipment/' . $equipment->getId()?->toRfc4122(),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeEquipment(Equipment $equipment): array
    {
        return [
            'id' => $equipment->getId()?->toRfc4122(),
            'poolId' => $equipment->getPool()?->getId()?->toRfc4122(),
            'name' => $equipment->getName(),
            'location' => $equipment->getLocation(),
            'type' => $equipment->getType(),
            'model' => $equipment->getModel(),
            'code' => $equipment->getCode(),
            'status' => $equipment->getStatus()?->value,
            'mainPhotoUrl' => $equipment->getMainPhoto()?->getUrl(),
            'createdAt' => $equipment->getCreatedAt()?->format(DATE_ATOM),
            'deletedAt' => $equipment->getDeletedAt()?->format(DATE_ATOM),
        ];
    }
}
