<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/notifications', name: 'notification_')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getNotifications(): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
