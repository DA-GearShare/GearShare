<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/reservations', name: 'reservation_history_')]
final class ReservationHistoryController extends AbstractController
{
    #[Route('/{reservationId}/changes', name: 'changes', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getChanges(Uuid $reservationId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/{reservationId}/transfers', name: 'transfers', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getTransfers(Uuid $reservationId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
