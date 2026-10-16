<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/users', name: 'user_')]
final class UserController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        UserRepository                         $repository,
        #[MapQueryParameter] ?string           $search = null,
        #[MapQueryParameter(name: 'page')] int $pageNumber = 1,
        #[MapQueryParameter] int               $pageSize = 20,
    ): JsonResponse
    {
        $pageNumber = max(1, $pageNumber);
        $pageSize = max(1, min(100, $pageSize));

        $page = $repository->findPaginated($search, $pageNumber, $pageSize);
        $totalItems = $page->getTotalCount();

        $items = array_map(fn(User $u) => [
            'id' => $u->getUserId(),
            'name' => $u->getDisplayName(),
            'email' => $u->getEmail(),
            'is_admin' => $u->isAdmin(),
            'is_teacher' => $u->isTeacher(),
        ], iterator_to_array($page));

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $pageNumber,
                'pageSize' => $pageSize,
                'totalItems' => $totalItems,
                'totalPages' => (int)ceil($totalItems / $pageSize),
            ],
        ]);
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
