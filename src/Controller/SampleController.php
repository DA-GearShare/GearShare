<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/sample', name: 'sample_')]
class SampleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function start(): JsonResponse
    {
        $data = [
            'success' => true,
            'payload' => [
                'message' => 'Data fetched successfully',
            ],
        ];
        return new JsonResponse($data);
    }
}
