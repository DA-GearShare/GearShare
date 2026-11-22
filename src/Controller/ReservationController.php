<?php

namespace App\Controller;

use App\Dto\ReservationCreateDto;
use App\Dto\ReservationIssueScanDto;
use App\Dto\ReservationItemIssueDto;
use App\Entity\Equipment;
use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use App\Repository\EquipmentRepository;
use App\Repository\ReservationRepository;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
    use SerializesEquipment;

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
        $authorizedReservation = $this->getAuthorizedReservation($repository, $reservationId);

        return $this->json($this->serializeReservation($authorizedReservation), Response::HTTP_OK);
    }


    /**
     * Returns the Reservation if the User is allowed to see it
     *
     * @param ReservationRepository $repository
     * @param Uuid $reservationId
     * @return Reservation|Response
     */
    public function getAuthorizedReservation(ReservationRepository $repository, Uuid $reservationId): Reservation|Response
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
        return $reservation;
    }

    #[Route('/{reservationId}/items', name: 'get_items', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getReservationItems(
        Uuid                  $reservationId,
        ReservationRepository $repository,
    ): Response
    {
        $authorizedReservation = $this->getAuthorizedReservation($repository, $reservationId);
        if ($authorizedReservation instanceof Response) return $authorizedReservation;

        $items = array_values(array_map(fn(ReservationItem $item) => $this->serializeReservationItem($item), $authorizedReservation->getItems()->toArray()));
        return $this->json($items, Response::HTTP_OK);
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

    #[Route('/{reservationId}/issue', name: 'global_issue', requirements: ['reservationId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function globalIssue(
        Uuid                   $reservationId,
        Request                $request,
        ReservationRepository  $reservationRepository,
        EquipmentRepository    $equipmentRepository,
        EntityManagerInterface $em,
    ): Response
    {
        $reservation = $this->findIssuableReservation($reservationRepository, $reservationId);
        if ($reservation instanceof Response) return $reservation;

        $parameters = $this->decodeJsonObject($request);
        if ($parameters instanceof Response) return $parameters;

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
                ['message' => sprintf('Kein Gerät mit code "%s" gefunden', $dto->code)],
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
                fn($key, ReservationItem $item) => $item->getId()?->equals($dto->replaceItemId) === true,
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
        Uuid                   $reservationId,
        Uuid                   $itemId,
        Request                $request,
        ReservationRepository  $reservationRepository,
        EquipmentRepository    $equipmentRepository,
        EntityManagerInterface $em,
    ): Response
    {
        $reservation = $this->findIssuableReservation($reservationRepository, $reservationId);
        if ($reservation instanceof Response) return $reservation;

        $parameters = $this->decodeJsonObject($request);
        if ($parameters instanceof Response) return $parameters;

        $dto = ReservationItemIssueDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $item = $reservation->getItems()->findFirst(
            fn($key, ReservationItem $item) => $item->getId()?->equals($itemId) === true,
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
                ['message' => sprintf('Kein Gerät mit code "%s" gefunden', $dto->code)],
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

    /**
     * Returns the reservation if it exists and its status allows issuing.
     */
    private function findIssuableReservation(ReservationRepository $repository, Uuid $reservationId): Reservation|JsonResponse
    {
        $reservation = $repository->find($reservationId);
        if ($reservation === null) {
            return new JsonResponse(['message' => 'Reservierung nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }
        if (!in_array($reservation->getStatus(), [ReservationStatus::Requested, ReservationStatus::Active], true)) {
            return new JsonResponse(['message' => 'Reservierung erlaubt keine Ausgabe.'], Response::HTTP_CONFLICT);
        }
        return $reservation;
    }

    /**
     * Decodes the request body, which must be a JSON object.
     */
    private function decodeJsonObject(Request $request): array|JsonResponse
    {
        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['message' => 'Malformed JSON'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new JsonResponse(['message' => 'Body muss ein JSON Objekt sein'], Response::HTTP_BAD_REQUEST);
        }
        return $parameters;
    }

    private function findItemByEquipment(Reservation $reservation, Equipment $equipment): ?ReservationItem
    {
        return $reservation->getItems()->findFirst(
            fn($key, ReservationItem $item) => $item->getRequestedEquipment()?->getId()?->equals($equipment->getId()) === true,
        );
    }

    /**
     * Equipment can be issued for the reservation if it is not deleted and not blocked by another reservation.
     */
    private function isEquipmentIssuable(Equipment $equipment, Reservation $reservation, ReservationRepository $repository): bool
    {
        return !$equipment->isDeleted() && $repository->isEquipmentAvailable(
                $equipment,
                $reservation->getStartDatetime(),
                $reservation->getEndDatetime(),
                $reservation,
            );
    }

    /**
     * Marks an existing position as issued (re-issues returned positions if the equipment is still available).
     */
    private function issueExistingItem(
        ReservationItem        $item,
        Reservation            $reservation,
        ReservationRepository  $repository,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        switch ($item->getStatus()) {
            case ReservationItemStatus::Issued:
                return new JsonResponse(
                    ['message' => 'Dieses Gerät wurde bereits ausgegeben.'],
                    Response::HTTP_CONFLICT,
                );
            case ReservationItemStatus::Returned:
                // A returned position doesn't block the equipment
                $available = $repository->isEquipmentAvailable(
                    $item->getRequestedEquipment(),
                    $reservation->getStartDatetime(),
                    $reservation->getEndDatetime(),
                    $reservation,
                );
                if (!$available) {
                    return new JsonResponse(
                        ['message' => 'Das Gerät ist im Zeitraum der Reservierung nicht verfügbar.'],
                        Response::HTTP_CONFLICT,
                    );
                }
            // fallthrough
            case ReservationItemStatus::Requested:
                $item->setStatus(ReservationItemStatus::Issued);
                $item->setIssuedAt(new DateTimeImmutable());
                $item->setReturnedAt(null);
                $reservation->setStatus(ReservationStatus::Active);

                $em->persist($item);
                $em->persist($reservation);
                $em->flush();

                return $this->json($this->serializeReservation($reservation), Response::HTTP_OK);
        }

        return new JsonResponse(['message' => 'Unbekannter Positionsstatus.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Adds the equipment as a new issued position and activates the reservation.
     */
    private function addIssuedItem(Reservation $reservation, Equipment $equipment, EntityManagerInterface $em): JsonResponse
    {
        $reservation->addItem(
            new ReservationItem()
                ->setRequestedEquipment($equipment)
                ->setStatus(ReservationItemStatus::Issued)
                ->setIssuedAt(new DateTimeImmutable()))
            ->setStatus(ReservationStatus::Active);

        $em->persist($reservation);
        $em->flush();

        return $this->json($this->serializeReservation($reservation), Response::HTTP_OK);
    }
}
