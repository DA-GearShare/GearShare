<?php

namespace App\Controller;

use App\Dto\ReservationCreateDto;
use App\Entity\ReservationItem;
use App\Entity\User;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use App\Service\ReservationServices;
use DateInvalidTimeZoneException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/reservations', name: 'reservation_')]
final class ReservationController extends AbstractController
{
    use ReservationServices;

    #[Route('', name: 'list', methods: ['GET'])] // TODO: add "code" field to Req-Body
    public function getReservations(
        ReservationRepository $repository,
        #[MapQueryParameter]
        ?Uuid $userId,
        #[MapQueryParameter]
        ?ReservationStatus $status,
        #[MapQueryParameter]
        ?bool $overdue,
        #[MapQueryParameter]
        int $page = 1,
        #[MapQueryParameter]
        int $pageSize = 20,
    ): Response {
        if ($page < 1 || $pageSize < 1) {
            return new Response('Ungültige Paginierung.', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $current = $this->getUser();
            if (!$current instanceof User) {
                return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);
            }
            $userId = $current->getUserId();
        }

        $result = $repository->findPaginated($userId, $status, $overdue, $page, $pageSize);

        $items = array_map(fn ($e) => $this->serializeReservation($e), $result['items']);

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $result['totalItems'],
                'totalPages' => (int) ceil($result['totalItems'] / $pageSize),
            ],
        ]);
    }

    #[Route('/{reservationId}', name: 'get', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getReservation(
        Uuid $reservationId,
        ReservationRepository $repository,
    ): Response {
        $authorizedReservation = $this->getAuthorizedReservation($repository, $reservationId);

        return $this->json($this->serializeReservation($authorizedReservation), Response::HTTP_OK);
    }

    #[Route('/{reservationId}/items', name: 'get_items', requirements: ['reservationId' => Requirement::UUID], methods: ['GET'])]
    public function getReservationItems(
        Uuid $reservationId,
        ReservationRepository $repository,
    ): Response {
        $authorizedReservation = $this->getAuthorizedReservation($repository, $reservationId);
        if ($authorizedReservation instanceof Response) {
            return $authorizedReservation;
        }

        $items = array_values(array_map(fn (ReservationItem $item) => $this->serializeReservationItem($item), $authorizedReservation->getItems()->toArray()));

        return $this->json($items, Response::HTTP_OK);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createReservation(
        Request $request,
        ReservationRepository $repository,
    ): Response {
        try {
            $parameters = json_decode($request->getContent(), true, 8, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!\is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new Response('Body muss ein JSON Objekt sein', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('Nicht angemeldet.', Response::HTTP_UNAUTHORIZED);
        }

        $dto = ReservationCreateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $reservation = $repository->createFromDto($dto, $user);
        if ($reservation instanceof Response) {
            return $reservation;
        }

        return $this->json($this->serializeReservation($reservation), Response::HTTP_CREATED);
    }

    #[Route('/overdue', name: 'get_overdue', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getOverdueReservations(
        ReservationRepository $repository,
    ): Response {
        $result = $repository->findOverdue();

        $items = array_map(fn ($e) => $this->serializeReservation($e), $result);

        return $this->json(['items' => $items]);
    }

    #[Route('/{reservationId}', name: 'edit', requirements: ['reservationId' => Requirement::UUID], methods: ['PATCH'])]
    public function editReservation(Uuid $reservationId, Request $request): Response
    {
        // TODO: implement
        return $this->json(['error' => 'Not implemented'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
