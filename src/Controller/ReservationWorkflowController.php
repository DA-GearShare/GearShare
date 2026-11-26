<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/reservations', name: 'reservation_workflow_')]
final class ReservationWorkflowController extends AbstractController
{
    #[Route('/{reservationId}/reject', name: 'reject', requirements: ['reservationId' => Requirement::UUID], methods: ['POST'])]
    public function reject(Uuid $reservationId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/{reservationId}/transfer', name: 'transfer', requirements: ['reservationId' => Requirement::UUID], methods: ['POST'])]
    public function transfer(Uuid $reservationId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
