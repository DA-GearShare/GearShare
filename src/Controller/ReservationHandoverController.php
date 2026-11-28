<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ReservationIssueScanDto;
use App\Dto\ReservationItemIssueDto;
use App\Entity\ReservationItem;
use App\Enum\ReservationItemStatus;
use App\Repository\EquipmentRepository;
use App\Repository\ReservationRepository;
use App\Service\EquipmentServices;
use App\Service\ReservationServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/reservations', name: 'reservation_handover_')]
final class ReservationHandoverController extends AbstractController
{
    use EquipmentServices;
    use ReservationServices;

    #[Route('/{reservationId}/issue', name: 'reservation_issue', requirements: ['reservationId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reservationItemIssue(
        Uuid $reservationId,
        Request $request,
        ReservationRepository $reservationRepository,
        EquipmentRepository $equipmentRepository,
        EntityManagerInterface $em,
    ): Response {
        $reservation = $this->findIssuableReservation($reservationRepository, $reservationId);
        if ($reservation instanceof Response) {
            return $reservation;
        }

        $parameters = $this->decodeJsonObject($request);
        if ($parameters instanceof Response) {
            return $parameters;
        }

        $dto = ReservationIssueScanDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $equipment = $equipmentRepository->findByCode($dto->code);
        if ($equipment === null) {
            return new JsonResponse(
                ['message' => \sprintf('Kein Gerät mit code "%s" gefunden', $dto->code)],
                Response::HTTP_NOT_FOUND,
            );
        }

        $item = $this->findItemByEquipment($reservation, $equipment);

        // Equipment part of reservation
        if ($item !== null) {
            return $this->issueExistingItem($item, $reservation, $reservationRepository, $em);
        }

        // Equipment not part of reservation
        if (!$this->isEquipmentIssuable($equipment, $reservation, $reservationRepository)) {
            return new JsonResponse(
                ['message' => 'Das Gerät ist im Zeitraum der Reservierung nicht verfügbar.'],
                Response::HTTP_CONFLICT,
            );
        }

        if (!$dto->confirmAdd) {
            return new JsonResponse([
                'message' => 'Das gescannte Gerät ist nicht Teil dieser Reservierung.',
                'equipment' => $this->serializeEquipment($equipment),
            ], Response::HTTP_CONFLICT);
        }

        if ($dto->replaceItemId !== null) {
            $replacedItem = $reservation->getItems()->findFirst(
                static fn ($key, ReservationItem $item) => $item->getId()?->equals($dto->replaceItemId) === true,
            );

            if ($replacedItem === null) {
                return new JsonResponse(
                    ['message' => 'Die zu ersetzende Position gehört nicht zu dieser Reservierung.'],
                    Response::HTTP_NOT_FOUND,
                );
            }

            // Only replace requested Equipment (History)
            if ($replacedItem->getStatus() !== ReservationItemStatus::Requested) {
                return new JsonResponse(
                    ['message' => 'Die zu ersetzende Position wurde bereits ausgegeben.'],
                    Response::HTTP_CONFLICT,
                );
            }

            $reservation->removeItem($replacedItem); // orphanRemoval löscht das Item beim flush
        }

        return $this->addIssuedItem($reservation, $equipment, $em);
    }

    #[Route('/{reservationId}/items/{itemId}/issue', name: 'item_issue', requirements: ['reservationId' => Requirement::UUID, 'itemId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function itemIssue(
        Uuid $reservationId,
        Uuid $itemId,
        Request $request,
        ReservationRepository $reservationRepository,
        EquipmentRepository $equipmentRepository,
        EntityManagerInterface $em,
    ): Response {
        $reservation = $this->findIssuableReservation($reservationRepository, $reservationId);
        if ($reservation instanceof Response) {
            return $reservation;
        }

        $parameters = $this->decodeJsonObject($request);
        if ($parameters instanceof Response) {
            return $parameters;
        }

        $dto = ReservationItemIssueDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $item = $reservation->getItems()->findFirst(
            static fn ($key, ReservationItem $item) => $item->getId()?->equals($itemId) === true,
        );
        if ($item === null) {
            return new JsonResponse(
                ['message' => 'Position gehört nicht zu dieser Reservierung.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $equipment = $equipmentRepository->findByCode($dto->code);
        if ($equipment === null) {
            return new JsonResponse(
                ['message' => \sprintf('Kein Gerät mit code "%s" gefunden', $dto->code)],
                Response::HTTP_NOT_FOUND,
            );
        }

        // Scanned equipment matches the position
        if ($item->getRequestedEquipment()?->getId()?->equals($equipment->getId()) === true) {
            return $this->issueExistingItem($item, $reservation, $reservationRepository, $em);
        }

        // Scanned equipment differs -> replace position
        if ($this->findItemByEquipment($reservation, $equipment) !== null) {
            return new JsonResponse(
                ['message' => 'Das gescannte Gerät ist bereits eine andere Position dieser Reservierung.'],
                Response::HTTP_CONFLICT,
            );
        }

        // Only replace requested Equipment (History)
        if ($item->getStatus() !== ReservationItemStatus::Requested) {
            return new JsonResponse(
                ['message' => 'Die zu ersetzende Position wurde bereits ausgegeben oder zurückgegeben.'],
                Response::HTTP_CONFLICT,
            );
        }

        if (!$this->isEquipmentIssuable($equipment, $reservation, $reservationRepository)) {
            return new JsonResponse(
                ['message' => 'Das Gerät ist im Zeitraum der Reservierung nicht verfügbar.'],
                Response::HTTP_CONFLICT,
            );
        }

        $reservation->removeItem($item); // orphanRemoval löscht das Item beim flush

        return $this->addIssuedItem($reservation, $equipment, $em);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/issue', name: 'global_issue', methods: ['POST'])]
    public function globalItemIssue(Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{reservationId}/items/{itemId}/return', name: 'item_return', requirements: ['reservationId' => Requirement::UUID, 'itemId' => Requirement::UUID], methods: ['POST'])]
    public function itemReturn(Uuid $reservationId, Uuid $itemId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
