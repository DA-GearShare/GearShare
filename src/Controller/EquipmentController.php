<?php
namespace App\Controller;

use App\Repository\EquipmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use App\Repository\ReservationItemRepository;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getEquipments(
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
            'availableInRequestedPeriod' => $fromDate !== null
                ? !in_array($e->getId()->toRfc4122(), $reservedIds, true)
                : $e->getStatus()->value === 'available'
        ], $accessories);
        return $this->json($items);
    }
}
