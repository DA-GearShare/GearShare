<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/opening-hours', name: 'opening_hours_')]
final class OpeningHoursController extends AbstractController
{
    #[Route('/effective', name: 'effective', methods: ['GET'])]
    public function getEffective(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    // --- Recurring ---

    #[Route('/recurring', name: 'recurring_list', methods: ['GET'])]
    public function getRecurring(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/recurring', name: 'recurring_create', methods: ['POST'])]
    public function createRecurring(Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/recurring/{recurringId}', name: 'recurring_edit', requirements: ['recurringId' => Requirement::UUID], methods: ['PUT'])]
    public function editRecurring(Uuid $recurringId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/recurring/{recurringId}', name: 'recurring_delete', requirements: ['recurringId' => Requirement::UUID], methods: ['DELETE'])]
    public function deleteRecurring(Uuid $recurringId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    // --- Exceptions ---

    #[Route('/exceptions', name: 'exception_list', methods: ['GET'])]
    public function getExceptions(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/exceptions', name: 'exception_create', methods: ['POST'])]
    public function createException(Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/exceptions/{exceptionId}', name: 'exception_edit', requirements: ['exceptionId' => Requirement::UUID], methods: ['PUT'])]
    public function editException(Uuid $exceptionId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/exceptions/{exceptionId}', name: 'exception_delete', requirements: ['exceptionId' => Requirement::UUID], methods: ['DELETE'])]
    public function deleteException(Uuid $exceptionId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
