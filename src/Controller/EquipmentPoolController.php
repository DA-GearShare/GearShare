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

#[Route('/api/v1/equipment-pools', name: 'equipment_pool_')]
final class EquipmentPoolController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getPools(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function createPool(Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/{poolId}', name: 'get', requirements: ['poolId' => Requirement::UUID], methods: ['GET'])]
    public function getPool(Uuid $poolId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{poolId}', name: 'edit', requirements: ['poolId' => Requirement::UUID], methods: ['PUT'])]
    public function editPool(Uuid $poolId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{poolId}', name: 'delete', requirements: ['poolId' => Requirement::UUID], methods: ['DELETE'])]
    public function deletePool(Uuid $poolId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
