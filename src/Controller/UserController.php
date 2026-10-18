<?php

namespace App\Controller;

use App\Dto\UserRolesDto;
use App\Entity\User;
use App\Repository\UserRepository;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
            'isAdmin' => $u->isAdmin(),
            'isTeacher' => $u->isTeacher(),
        ], iterator_to_array($page));

        return new JsonResponse([
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
    public function roles(
        UserRepository $userRepository,
        Request        $request,
        string         $userId
    ): Response
    {
        $user = $userRepository->findOneById($userId);
        if ($user === null) return new Response(sprintf('User "%s" nicht gefunden.', $userId), Response::HTTP_NOT_FOUND);

        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['message' => 'Malformed JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new JsonResponse(['message' => 'Body muss ein JSON Objekt sein'], Response::HTTP_BAD_REQUEST);
        }

        $roles = UserRolesDto::fromArray($parameters);
        if ($roles->hasErrors()) return new JsonResponse(['message' => 'Invalider Request Body', 'details' => $roles->getErrors()], Response::HTTP_BAD_REQUEST);

        $current = $this->getUser();
        if (
            $roles->revokesAdmin()
            && $current instanceof User
            && $current->getUserId() === $user->getUserId()
        ) return new JsonResponse(['message' => 'Du kannst deinen eigenen Admin Status nicht entfernen'], Response::HTTP_CONFLICT);

        $userRepository->applyRoles($user, $roles);

        return new JsonResponse([
            'id' => $user->getUserId(),
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
            'is_admin' => $user->isAdmin(),
            'is_teacher' => $user->isTeacher(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{userId}', name: 'get_from_id', requirements: ['userId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function listFromId(UserRepository $userRepository, string $userId): Response
    {
        $user = $userRepository->findOneById($userId);
        if ($user == null) return new JsonResponse(sprintf('User "%s" not found.', $userId), Response::HTTP_NOT_FOUND);

        return new JsonResponse([
            'id' => $user->getUserId(),
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
            'is_admin' => $user->isAdmin(),
            'is_teacher' => $user->isAdmin(),
        ]);
    }
}
