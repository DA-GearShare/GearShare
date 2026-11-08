<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EquipmentCreateDto;
use App\Dto\EquipmentUpdateDto;
use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;
use App\Repository\ReservationItemRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getEquipments(
        EquipmentRepository          $repository,
        #[MapQueryParameter] string  $sort = 'name',
        #[MapQueryParameter] string  $order = 'asc',
        #[MapQueryParameter] ?string $from = null,
        #[MapQueryParameter] ?string $to = null,
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
        if (($from && !$to) || (!$from && $to)) {
            return new Response('from und to müssen beide angegeben werden.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate   = $to   ? new DateTimeImmutable($to)   : null;
        } catch (\Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate !== null && $fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }

        $result = $repository->findPaginated($sort, $order, $fromDate, $toDate, $search, $page, $pageSize);

        $items = array_map(fn($e) => $this->serializeEquipment($e), $result['items']);

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
    #[Route('/{equipmentId}', name: 'get', methods: ['GET'])]
    public function getEquipment(
        Uuid $equipmentId,
        EquipmentRepository $repository,
    ): Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeEquipment($equipment));
    }

    #[Route('/{equipmentId}/accessories', name: 'getAccessories', methods: ['GET'])]
    public function getAccessories(
        Uuid $equipmentId,
        EquipmentRepository $repository,
        ReservationItemRepository $reservationItemRepository,
        #[MapQueryParameter] ?string $from = null,
        #[MapQueryParameter] ?string $to = null,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if (($from && !$to) || (!$from && $to)) {
            return new Response('from und to müssen beide angegeben werden.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate   = $to   ? new DateTimeImmutable($to)   : null;
        } catch (\Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate !== null && $fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }

        $accessories = $equipment->getAccessories()->filter(fn($accessory) => !$accessory->isDeleted())->getValues();

        $reservedIds = [];
        if ($fromDate !== null) {
            $reservedIds = $reservationItemRepository->findReservedEquipmentIds($accessories, $fromDate, $toDate);
        }

        $items = array_map(fn($e) => [
            'accessoryEquipmentId' => $e->getId(),
            'name' => $e->getName(),
            'availableInRequestedPeriod' => !in_array($e->getId()->toRfc4122(), $reservedIds, true),
        ], $accessories);
        return $this->json($items);
    }

    #[Route('/{equipmentId}/accessories', name: 'addAccessories', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addAccessories(
        Uuid $equipmentId,
        Request $request,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $data = $request->toArray();
        $accessoryId = $data['accessoryEquipmentId'] ?? null;

        if (!is_string($accessoryId) || !Uuid::isValid($accessoryId)) {
            return new Response('accessoryEquipmentId fehlt oder ist ungültig.', Response::HTTP_BAD_REQUEST);
        }

        $accessory = $repository->find(Uuid::fromString($accessoryId));
        if ($accessory === null || $accessory->isDeleted()) {
            return new Response('Zubehör nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        if ($accessory === $equipment) {
            return new Response('Ein Gerät kann nicht sein eigenes Zubehör sein.', Response::HTTP_BAD_REQUEST);
        }
        if ($equipment->getAccessories()->contains($accessory)) {
            return new Response('Verknüpfung besteht bereits.', Response::HTTP_CONFLICT);
        }
        $equipment->addAccessory($accessory);
        $entityManager->flush();

        return $this->json([
            'accessoryEquipmentId' => $accessory->getId(),
            'name' => $accessory->getName(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{equipmentId}/accessories/{accessoryId}', name: 'removeAccessories', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeAccessories(
        Uuid $equipmentId,
        Uuid $accessoryId,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $accessory = $repository->find($accessoryId);
        if ($accessory === null || !$equipment->getAccessories()->contains($accessory)) {
            return new Response('Verknüpfung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        $equipment->removeAccessory($accessory);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function createEquipment(
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}', name: 'edit', methods: ['PUT'])]
    public function editEquipment(
        Uuid $equipmentId,
        Request $request,
        EquipmentRepository $repository,
    ): Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new Response('Body muss ein JSON Objekt sein', Response::HTTP_BAD_REQUEST);
        }

        $dto = EquipmentUpdateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $repository->updateFromDto($equipment, $dto);
        if ($result instanceof Response) {
            return $result;
        }

        return $this->json($this->serializeEquipment($equipment));
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
            'mainPhotoUrl' => $equipment->getMainPhoto()?->getUrl(),
        ];
    }
}
