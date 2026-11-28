<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use App\Repository\ReservationItemRepository;
use App\Service\EquipmentServices;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/equipment', name: 'availability_')]
final class AvailabilityController extends AbstractController
{
    use EquipmentServices;

    #[Route('/calendar', name: 'get_calendar', methods: ['POST'])]
    public function getCalendar(
        Request $request,
        EquipmentRepository $repository,
        ReservationItemRepository $reservationItemRepository,
    ): Response {
        $data = $request->toArray();
        $ids = $data['equipmentIds'] ?? [];
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;

        if (!\is_array($ids) || \count($ids) === 0) {
            return new Response('equipmentIds muss eine nicht leere Liste sein.', Response::HTTP_BAD_REQUEST);
        }
        if (\count($ids) > 10) {
            return new Response('Es können maximal 10 Geräte ausgewählt werden.', Response::HTTP_BAD_REQUEST);
        }
        if (!\is_string($from) || !\is_string($to)) {
            return new Response('from und to müssen angegeben werden.', Response::HTTP_BAD_REQUEST);
        }
        try {
            $fromDate = new DateTimeImmutable($from);
            $toDate = new DateTimeImmutable($to);
        } catch (Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }
        if ($fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }
        $equipmentList = [];
        foreach (array_unique($ids) as $id) {
            if (!\is_string($id) || !Uuid::isValid($id)) {
                return new Response('Ungültige  ID.'.$id, Response::HTTP_BAD_REQUEST);
            }
            $equipment = $repository->find(Uuid::fromString($id));
            if ($equipment === null || $equipment->isDeleted()) {
                return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
            }
            $equipmentList[] = $equipment;
        }
        $reservationItems = $reservationItemRepository->findInPeriod($equipmentList, $fromDate, $toDate);

        $notAvailable = [];
        foreach ($equipmentList as $equipment) {
            $notAvailable[$equipment->getId()->toRfc4122()] = [];
        }
        foreach ($reservationItems as $item) {
            $reservation = $item->getReservation();
            $startDatetime = $reservation->getStartDatetime();
            $endDatetime = $reservation->getEndDatetime();

            $requestedEquipment = $item->getRequestedEquipment();
            if ($requestedEquipment === null) {
                continue;
            }

            $notAvailable[$requestedEquipment->getId()->toRfc4122()][] = [
                'from' => $startDatetime->format(\DATE_ATOM),
                'to' => $endDatetime->format(\DATE_ATOM),
            ];
        }

        return $this->json(array_map(fn (Equipment $e) => [
            ...$this->serializeEquipment($e),
            'notAvailable' => $notAvailable[$e->getId()->toRfc4122()],
        ], $equipmentList));
    }
}
