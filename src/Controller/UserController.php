<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
            'is_admin' => $u->isAdmin(),
            'is_teacher' => $u->isTeacher(),
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
        UserRepository         $userRepository,
        EntityManagerInterface $em,
        Request                $request,
        string                 $userId
    ): Response
    {
        $user = $userRepository->findOneById($userId);
        if ($user == null) return new Response(sprintf('User "%s" not found.', $userId), Response::HTTP_NOT_FOUND);

        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Malformed JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new JsonResponse(['error' => 'Body must be a JSON object'], Response::HTTP_BAD_REQUEST);
        }

        $allowed = ['isAdmin', 'isTeacher'];
        $errors = [];

        foreach (array_diff(array_keys($parameters), $allowed) as $key) {
            $errors[(string)$key] = 'Unknown field';
        }

        foreach ($allowed as $field) {
            if (array_key_exists($field, $parameters) && !is_bool($parameters[$field])) {
                $errors[$field] = 'Must be a boolean';
            }
        }

        if (!array_intersect($allowed, array_keys($parameters))) {
            $errors['body'] = 'Provide at least one of isAdmin or isTeacher';
        }

        if ($errors) {
            return new JsonResponse(['error' => 'Invalid request body', 'details' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $current = $this->getUser();
        if (
            ($parameters['isAdmin'] ?? true) === false
            && $current instanceof User
            && $current->getUserId() === $user->getUserId()
        ) return new JsonResponse(['error' => 'You cannot remove your own admin status'], Response::HTTP_CONFLICT);

        if (array_key_exists('isAdmin', $parameters)) $user->setIsAdmin($parameters['isAdmin']);
        if (array_key_exists('isTeacher', $parameters)) $user->setIsTeacher($parameters['isTeacher']);
        $em->flush();

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
