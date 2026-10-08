<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/user', name: 'user_')]
final class UserController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(UserRepository $userRepository): Response
    {
        $user = $userRepository->findAll();
        $items = array_map(fn($e) => [
            'id' => $e->getUserId(),
            'name' => $e->getDisplayName(),
            'is_admin' => $e->isAdmin(),
            'is_teacher' => $e->isAdmin(),
            'email' => $e->getEmail(),
        ], $user);

        return $this->json(['items' => $items]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{userId}/roles', name: 'set_role', requirements: ['userId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['PATCH'])]
    public function roles(UserRepository $userRepository, string $userId): Response
    {
        $user = $userRepository->findOneById($userId);
        if ($user == null) throw new NotFoundHttpException(sprintf('User "%s" not found.', $userId));
//        $parameters = json_decode($request->getContent(), true);
//        echo
        return $this->json([
            'id' => $user->getUserId(),
            'name' => $user->getDisplayName(),
            'is_admin' => $user->isAdmin(),
            'is_teacher' => $user->isAdmin(),
            'email' => $user->getEmail(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{userId}', name: 'get_from_id', requirements: ['userId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function listFromId(UserRepository $userRepository, string $userId): Response
    {
        $user = $userRepository->findOneById($userId);
        if ($user == null) throw new NotFoundHttpException(sprintf('User "%s" not found.', $userId));

        return $this->json([
            'id' => $user->getUserId(),
            'name' => $user->getDisplayName(),
            'is_admin' => $user->isAdmin(),
            'is_teacher' => $user->isAdmin(),
            'email' => $user->getEmail(),
        ]);
    }
}
