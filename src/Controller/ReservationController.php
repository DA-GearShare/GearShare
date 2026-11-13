<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/reservations', name: 'reservation_')]
final class ReservationController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getReservations(
        ReservationRepository                   $repository,
        #[MapQueryParameter] ?Uuid              $userId,
        #[MapQueryParameter] ?ReservationStatus $status,
        #[MapQueryParameter] ?bool              $overdue,
        #[MapQueryParameter] int                $page = 1,
        #[MapQueryParameter] int                $pageSize = 20,
    ): Response
    {
        if ($page < 1 || $pageSize < 1) {
            return new Response('Ungültige Paginierung.', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isGranted('ROLE_ADMIN')) $userId = null;

        $result = $repository->findPaginated($userId, $status, $overdue, $page, $pageSize);

        $items = array_map(fn($e) => $this->serializeReservation($e), $result['items']);

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $result['totalItems'],
                'totalPages' => (int)ceil($result['totalItems'] / $pageSize),
            ],
        ]);
    }

    /**
     * @param Reservation $reservation
     * @return array
     */
    private function serializeReservation(Reservation $reservation): array
    {
        $items = $reservation->getItems()->toArray();

        $allItemsReturned = array_all(
            $items,
            fn(ReservationItem $item) => $item->getStatus() === ReservationItemStatus::Returned,
        );

        return [
            'id' => $reservation->getId()?->toRfc4122(),
            'requestedByUserId' => $reservation->getRequestedBy()?->getUserId()?->toRfc4122(),
            'responsibleUserId' => $reservation->getResponsibleUser()?->getUserId()?->toRfc4122(),
            'status' => $reservation->getStatus()?->value,
            'startDatetime' => $reservation->getStartDatetime()?->format(DATE_ATOM),
            'endDatetime' => $reservation->getEndDatetime()?->format(DATE_ATOM),
            'isOverdue' => $reservation->getEndDatetime() < new DateTimeImmutable() && !$allItemsReturned,
            'createdAt' => $reservation->getCreatedAt()?->format(DATE_ATOM),
            'items' => array_values(array_map(fn(ReservationItem $item) => $this->serializeReservationItem($item), $items)),
        ];
    }

    /**
     * @param ReservationItem $item
     * @return array
     */
    private function serializeReservationItem(ReservationItem $item): array
    {
        return [
            'id' => $item->getId()?->toRfc4122(),
            'reservationId' => $item->getReservation()?->getId()?->toRfc4122(),
            'requestedEquipmentId' => $item->getRequestedEquipment()?->getId()?->toRfc4122(),
            'requestedPoolId' => $item->getRequestedPool()?->getId()?->toRfc4122(),
            'assignedEquipmentId' => $item->getAssignedEquipment()?->getId()?->toRfc4122(),
            'status' => $item->getStatus()?->value,
            'issuedAt' => $item->getIssuedAt()?->format(DATE_ATOM),
            'returnedAt' => $item->getReturnedAt()?->format(DATE_ATOM),
        ];
    }
}
