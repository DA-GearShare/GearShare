<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Repository\ReservationItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/equipment', name: 'equipment_accessory_')]
final class EquipmentAccessoryController extends AbstractController
{
    #[Route('/{equipmentId}/accessories', name: 'getAccessories', requirements: ['equipmentId' => Requirement::UUID], methods: ['GET'])]
    public function getAccessories(
        Uuid $equipmentId,
        EquipmentRepository $repository,
        ReservationItemRepository $reservationItemRepository,
        #[MapQueryParameter]
        ?string $from = null,
        #[MapQueryParameter]
        ?string $to = null,
    ): Response {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if (($from && !$to) || (!$from && $to)) {
            return new Response('from und to müssen beide angegeben werden.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate = $to ? new DateTimeImmutable($to) : null;
        } catch (Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate !== null && $fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }

        $accessories = $equipment->getAccessories()->filter(static fn ($accessory) => !$accessory->isDeleted())->getValues();

        $reservedIds = [];
        if ($fromDate !== null) {
            $reservedIds = $reservationItemRepository->findReservedEquipmentIds($accessories, $fromDate, $toDate);
        }

        $items = array_map(static fn ($e) => [
            'accessoryEquipmentId' => $e->getId(),
            'name' => $e->getName(),
            'availableInRequestedPeriod' => !\in_array($e->getId()->toRfc4122(), $reservedIds, true),
        ], $accessories);

        return $this->json($items);
    }

    #[Route('/{equipmentId}/accessories', name: 'addAccessories', requirements: ['equipmentId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addAccessories(
        Uuid $equipmentId,
        Request $request,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ): Response {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $data = $request->toArray();
        $accessoryId = $data['accessoryEquipmentId'] ?? null;

        if (!\is_string($accessoryId) || !Uuid::isValid($accessoryId)) {
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

    #[Route('/{equipmentId}/accessories/{accessoryId}', name: 'removeAccessories', requirements: ['equipmentId' => Requirement::UUID, 'accessoryId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeAccessories(
        Uuid $equipmentId,
        Uuid $accessoryId,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ): Response {
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
}
