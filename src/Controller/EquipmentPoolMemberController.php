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

#[Route('/api/v1/equipment-pools', name: 'equipment_pool_member_')]
final class EquipmentPoolMemberController extends AbstractController
{
    #[Route('/{poolId}/equipment', name: 'list', requirements: ['poolId' => Requirement::UUID], methods: ['GET'])]
    public function getMembers(Uuid $poolId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{poolId}/equipment', name: 'add', requirements: ['poolId' => Requirement::UUID], methods: ['POST'])]
    public function addMember(Uuid $poolId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{poolId}/equipment/{equipmentId}', name: 'remove', requirements: ['poolId' => Requirement::UUID, 'equipmentId' => Requirement::UUID], methods: ['DELETE'])]
    public function removeMember(Uuid $poolId, Uuid $equipmentId): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
