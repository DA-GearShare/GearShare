<?php

namespace App\Controller;

use App\Dto\ReservationCreateDto;
use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

        if (!$this->isGranted('ROLE_ADMIN')) {
            $current = $this->getUser();
            if (!$current instanceof User) return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);
            $userId = $current->getUserId();
        }

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

    #[Route('/{reservationId}', name: 'get', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getReservation(
        Uuid                  $reservationId,
        ReservationRepository $repository,
    ): Response
    {
        $reservation = $repository->find($reservationId);
        if (!$reservation) {
            return new Response('Reservierung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $current = $this->getUser();
            if (!$current instanceof User) return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);

            $currentId = $current->getUserId();
            $isOwn = $reservation->getRequestedBy()?->getUserId()?->equals($currentId)
                || $reservation->getResponsibleUser()?->getUserId()?->equals($currentId);
            if (!$isOwn) return new Response('Kein Zugriff auf diese Reservierung.', Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serializeReservation($reservation), Response::HTTP_OK);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createReservation(
        Request               $request,
        ReservationRepository $repository,
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

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);
        }

        $dto = ReservationCreateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $reservation = $repository->createFromDto($dto, $user);
        if ($reservation instanceof Response) {
            return $reservation;
        }

        return $this->json($this->serializeReservation($reservation), Response::HTTP_CREATED);
    }

    #[Route('/overdue', name: 'get_overdue', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getOverdueReservations(
        ReservationRepository $repository,
    ): Response
    {
        $result = $repository->findOverdue();

        $items = array_map(fn($e) => $this->serializeReservation($e), $result);

        return $this->json(['items' => $items]);
    }
}
