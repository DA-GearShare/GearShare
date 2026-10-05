<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function start(): JsonResponse
    {
        $data = [
            'success' => true,
            'payload' => [
                'message' => 'You are an Admin!'
            ],
        ];
        return new JsonResponse($data);
    }
}
