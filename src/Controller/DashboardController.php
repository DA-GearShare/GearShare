<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_ADMIN')]
#[Route('/api/v1', name: 'dashboard_')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard/active-loans', name: 'active_loans', methods: ['GET'])]
    public function getActiveLoans(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/dashboard/active-loans/{equipmentId}', name: 'active_loan', requirements: ['equipmentId' => Requirement::UUID], methods: ['GET'])]
    public function getActiveLoan(Uuid $equipmentId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/equipment/{equipmentId}/loan-history', name: 'loan_history', requirements: ['equipmentId' => Requirement::UUID], methods: ['GET'])]
    public function getLoanHistory(Uuid $equipmentId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
