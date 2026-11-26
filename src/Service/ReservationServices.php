<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\Reservation;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationItemStatus;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Shared serialization of Equipment, matching the `Equipment` schema in docs/api.yaml.
 */
trait ReservationServices
{
    /**
     * Returns the Reservation if the User is allowed to see it.
     */
    public function getAuthorizedReservation(ReservationRepository $repository, Uuid $reservationId): Reservation|Response
    {
        $reservation = $repository->find($reservationId);
        if (!$reservation) {
            return new Response('Reservierung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $current = $this->getUser();
            if (!$current instanceof User) {
                return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);
            }

            $currentId = $current->getUserId();
            $isOwn = $reservation->getRequestedBy()?->getUserId()?->equals($currentId)
                || $reservation->getResponsibleUser()?->getUserId()?->equals($currentId);
            if (!$isOwn) {
                return new Response('Kein Zugriff auf diese Reservierung.', Response::HTTP_FORBIDDEN);
            }
        }

        return $reservation;
    }

    private function serializeReservation(Reservation $reservation): array
    {
        $items = $reservation->getItems()->toArray();

        $allItemsReturned = array_all(
            $items,
            static fn (ReservationItem $item) => $item->getStatus() === ReservationItemStatus::Returned,
        );

        return [
            'id' => $reservation->getId()?->toRfc4122(),
            'requestedByUserId' => $reservation->getRequestedBy()?->getUserId()?->toRfc4122(),
            'responsibleUserId' => $reservation->getResponsibleUser()?->getUserId()?->toRfc4122(),
            'status' => $reservation->getStatus()?->value,
            'startDatetime' => $reservation->getStartDatetime()?->format(\DATE_ATOM),
            'endDatetime' => $reservation->getEndDatetime()?->format(\DATE_ATOM),
            'isOverdue' => $reservation->getEndDatetime() < new DateTimeImmutable() && !$allItemsReturned,
            'createdAt' => $reservation->getCreatedAt()?->format(\DATE_ATOM),
            'items' => array_values(array_map(fn (ReservationItem $item) => $this->serializeReservationItem($item), $items)),
        ];
    }

    private function serializeReservationItem(ReservationItem $item): array
    {
        return [
            'id' => $item->getId()?->toRfc4122(),
            'reservationId' => $item->getReservation()?->getId()?->toRfc4122(),
            'requestedEquipmentId' => $item->getRequestedEquipment()?->getId()?->toRfc4122(),
            'requestedPoolId' => $item->getRequestedPool()?->getId()?->toRfc4122(),
            'status' => $item->getStatus()?->value,
            'issuedAt' => $item->getIssuedAt()?->format(\DATE_ATOM),
            'returnedAt' => $item->getReturnedAt()?->format(\DATE_ATOM),
        ];
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
        if (!\in_array($reservation->getStatus(), [ReservationStatus::Requested, ReservationStatus::Active], true)) {
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
            $parameters = json_decode($request->getContent(), true, 8, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['message' => 'Malformed JSON'], Response::HTTP_BAD_REQUEST);
        }
        if (!\is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new JsonResponse(['message' => 'Body muss ein JSON Objekt sein'], Response::HTTP_BAD_REQUEST);
        }

        return $parameters;
    }

    private function findItemByEquipment(Reservation $reservation, Equipment $equipment): ?ReservationItem
    {
        return $reservation->getItems()->findFirst(
            static fn ($key, ReservationItem $item) => $item->getRequestedEquipment()?->getId()?->equals($equipment->getId()) === true,
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
        ReservationItem $item,
        Reservation $reservation,
        ReservationRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
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
                // no break
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
                ->setIssuedAt(new DateTimeImmutable())
        )
            ->setStatus(ReservationStatus::Active);

        $em->persist($reservation);
        $em->flush();

        return $this->json($this->serializeReservation($reservation), Response::HTTP_OK);
    }
}
