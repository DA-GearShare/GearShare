<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EquipmentCreateDto;
use App\Dto\EquipmentUpdateDto;
use App\Entity\Equipment;
use App\Entity\EquipmentPhoto;
use App\Repository\EquipmentPhotoRepository;
use App\Repository\EquipmentRepository;
use App\Storage\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;
use App\Repository\ReservationItemRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[Route('/api/v1/equipment', name: 'equipment_')]
final class EquipmentController extends AbstractController
{
    private const string UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
    private const int MAX_PHOTO_BYTES = 15 * 1024 * 1024;

    public function __construct(
        #[Autowire('%env(MEDIA_BASE_URL)%')] private readonly string $mediaBaseUrl,
    )
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function getEquipments(
        EquipmentRepository          $repository,
        #[MapQueryParameter] string  $sort = 'name',
        #[MapQueryParameter] string  $order = 'asc',
        #[MapQueryParameter] ?string $from = null,
        #[MapQueryParameter] ?string $to = null,
        #[MapQueryParameter] ?string $search = null,
        #[MapQueryParameter] int     $page = 1,
        #[MapQueryParameter] int     $pageSize = 20,
    ): Response
    {
        if (!in_array($sort, ['name', 'location', 'type']) || !in_array($order, ['asc', 'desc'])) {
            return new Response('Ungültige Sortierung.', Response::HTTP_BAD_REQUEST);
        }
        if ($page < 1 || $pageSize < 1) {
            return new Response('Ungültige Paginierung.', Response::HTTP_BAD_REQUEST);
        }
        if (($from && !$to) || (!$from && $to)) {
            return new Response('from und to müssen beide angegeben werden.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate   = $to   ? new DateTimeImmutable($to)   : null;
        } catch (\Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate !== null && $fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }

        $result = $repository->findPaginated($sort, $order, $fromDate, $toDate, $search, $page, $pageSize);

        $items = array_map(fn($e) => [
            'id' => $e->getId(),
            'name' => $e->getName(),
            'location' => $e->getLocation(),
            'type' => $e->getType(),
            'model' => $e->getModel(),
        ], $result['items']);

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
    #[Route('/{equipmentId}', name: 'get', methods: ['GET'])]
    public function getEquipment(
        Uuid $equipmentId,
        EquipmentRepository $repository,
    ): Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeEquipment($equipment));
    }

    #[Route('/{equipmentId}/accessories', name: 'getAccessories', methods: ['GET'])]
    public function getAccessories(
        Uuid $equipmentId,
        EquipmentRepository $repository,
        ReservationItemRepository $reservationItemRepository,
        #[MapQueryParameter] ?string $from = null,
        #[MapQueryParameter] ?string $to = null,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if (($from && !$to) || (!$from && $to)) {
            return new Response('from und to müssen beide angegeben werden.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = $from ? new DateTimeImmutable($from) : null;
            $toDate   = $to   ? new DateTimeImmutable($to)   : null;
        } catch (\Exception) {
            return new Response('Ungültiges Datum.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate !== null && $fromDate >= $toDate) {
            return new Response('from muss vor to liegen.', Response::HTTP_BAD_REQUEST);
        }

        $accessories = $equipment->getAccessories()->filter(fn($accessory) => !$accessory->isDeleted())->getValues();

        $reservedIds = [];
        if ($fromDate !== null) {
            $reservedIds = $reservationItemRepository->findReservedEquipmentIds($accessories, $fromDate, $toDate);
        }

        $items = array_map(fn($e) => [
            'accessoryEquipmentId' => $e->getId(),
            'name' => $e->getName(),
            'availableInRequestedPeriod' => !in_array($e->getId()->toRfc4122(), $reservedIds, true),
        ], $accessories);
        return $this->json($items);
    }

    #[Route('/{equipmentId}/accessories', name: 'addAccessories', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addAccessories(
        Uuid $equipmentId,
        Request $request,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $data = $request->toArray();
        $accessoryId = $data['accessoryEquipmentId'] ?? null;

        if (!is_string($accessoryId) || !Uuid::isValid($accessoryId)) {
            return new Response('accessoryEquipmentId fehlt oder ist ungültig.', Response::HTTP_BAD_REQUEST);
        }

        $accessory = $repository->find(Uuid::fromString($accessoryId));
        if ($accessory === null || $accessory->isDeleted()) {
            return new Response('Zubehör nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        if ($accessory === $equipment) {
            return new Response('Ein Gerät kann nicht sein eigenes Zubehör sein.', Response::HTTP_BAD_REQUEST);
        }
        if ($equipment->getAccessories()->contains($accessory)) {
            return new Response('Verknüpfung besteht bereits.', Response::HTTP_CONFLICT);
        }
        $equipment->addAccessory($accessory);
        $entityManager->flush();

        return $this->json([
            'accessoryEquipmentId' => $accessory->getId(),
            'name' => $accessory->getName(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{equipmentId}/accessories/{accessoryId}', name: 'removeAccessories', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeAccessories(
        Uuid $equipmentId,
        Uuid $accessoryId,
        EquipmentRepository $repository,
        EntityManagerInterface $entityManager,
    ):Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $accessory = $repository->find($accessoryId);
        if ($accessory === null || !$equipment->getAccessories()->contains($accessory)) {
            return new Response('Verknüpfung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        $equipment->removeAccessory($accessory);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function createEquipment(
        Request             $request,
        EquipmentRepository $equipmentRepository,
    ): Response
    {
        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new Response('Body muss ein JSON Objekt sein', Response::HTTP_BAD_REQUEST);
        }

        $dto = EquipmentCreateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $equipment = $equipmentRepository->createFromDto($dto);
        if ($equipment instanceof Response) {
            return $equipment;
        }

        return $this->json($this->serializeEquipment($equipment), Response::HTTP_CREATED, [
            'Location' => '/api/v1/equipment/' . $equipment->getId()?->toRfc4122(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}', name: 'edit', methods: ['PUT'])]
    public function editEquipment(
        Uuid $equipmentId,
        Request $request,
        EquipmentRepository $repository,
    ): Response
    {
        $equipment = $repository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new Response('Body muss ein JSON Objekt sein', Response::HTTP_BAD_REQUEST);
        }

        $dto = EquipmentUpdateDto::fromArray($parameters);
        if ($dto->hasErrors()) {
            return new JsonResponse([
                'message' => 'Invalider Request Body',
                'errors' => $dto->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $repository->updateFromDto($equipment, $dto);
        if ($result instanceof Response) {
            return $result;
        }

        return $this->json($this->serializeEquipment($equipment));
    }

    /** @return array<string, mixed> */
    private function serializeEquipment(Equipment $equipment): array
    {
        return [
            'id' => $equipment->getId()?->toRfc4122(),
            'poolId' => $equipment->getPool()?->getId()?->toRfc4122(),
            'name' => $equipment->getName(),
            'location' => $equipment->getLocation(),
            'type' => $equipment->getType(),
            'model' => $equipment->getModel(),
            'code' => $equipment->getCode(),
            'status' => $equipment->getStatus()?->value,
//            'mainPhotoUrl' => $equipment->getMainPhoto()?->getUrl(),
            'createdAt' => $equipment->getCreatedAt()?->format(DATE_ATOM),
            'deletedAt' => $equipment->getDeletedAt()?->format(DATE_ATOM),
        ];
    }

    #[Route('/{equipmentId}/photos', name: 'photo_list', requirements: ['equipmentId' => self::UUID], methods: ['GET'])]
    public function listPhotos(
        #[MapEntity(id: 'equipmentId')] Equipment $equipment,
        EquipmentPhotoRepository                  $photos,
    ): Response
    {
        if ($equipment->isDeleted()) {
            return new Response('Gerät nicht gefunden', Response::HTTP_NOT_FOUND);
        }

        return $this->json(array_map(
            $this->serializePhoto(...),
            $photos->findForEquipment($equipment),
        ));
    }

    /**
     * @throws FilesystemException
     * @throws Throwable
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos', name: 'photo_upload', requirements: ['equipmentId' => self::UUID], methods: ['POST'])]
    public function uploadPhoto(
        #[MapEntity(id: 'equipmentId')] Equipment $equipment,
        Request                                   $request,
        PhotoStorage                              $storage,
        EquipmentPhotoRepository                  $photos,
    ): Response
    {
        if ($equipment->isDeleted()) {
            return new Response('Gerät nicht gefunden', Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new Response('Das Feld "file" fehlt', Response::HTTP_BAD_REQUEST);
        }
        if (!$file->isValid()) {
            return new Response($file->getErrorMessage(), Response::HTTP_BAD_REQUEST);
        }
        if ($file->getSize() > self::MAX_PHOTO_BYTES) {
            return new Response('Maximal 15 MB pro Foto', Response::HTTP_BAD_REQUEST);
        }

        // v4 = 122 Zufallsbits. Die UUID ist der einzige Schutz der öffentlichen URL.
        $id = Uuid::v4();
        $storage->putEquipment($file, $id); // wirft 415 bei falschem Dateityp

        try {
            $photo = $photos->createForEquipment(
                $equipment,
                $id,
                rtrim($this->mediaBaseUrl, '/') . '/equipment/' . $id->toRfc4122(),
                $request->request->getBoolean('isMain'),
            );
        } catch (Throwable $e) {
            try {
                $storage->deleteEquipment($id);
            } catch (Throwable) {
            }
            throw $e;
        }

        return $this->json($this->serializePhoto($photo), Response::HTTP_CREATED);
    }

    /** @return array<string, mixed> Form wie components.schemas.EquipmentPhoto in api.yaml */
    private function serializePhoto(EquipmentPhoto $photo): array
    {
        return [
            'id' => $photo->getId()->toRfc4122(),
            'equipmentId' => $photo->getEquipment()?->getId()?->toRfc4122(),
            'url' => $photo->getUrl(),
            'isMain' => $photo->isMain(),
            'uploadedAt' => $photo->getUploadedAt()?->format(DATE_ATOM),
        ];
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_update', requirements: ['equipmentId' => self::UUID, 'photoId' => self::UUID], methods: ['PATCH'])]
    public function updatePhoto(
        #[MapEntity(id: 'equipmentId')] Equipment  $equipment,
        #[MapEntity(id: 'photoId')] EquipmentPhoto $photo,
        Request                                    $request,
        EquipmentPhotoRepository                   $photos,
    ): Response
    {
        if ($notFound = $this->checkPhotoBelongsTo($photo, $equipment)) {
            return $notFound;
        }

        try {
            $parameters = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new Response('Malformed JSON', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($parameters) || !array_key_exists('isMain', $parameters) || !is_bool($parameters['isMain'])) {
            return new Response('Invalider Request Body', Response::HTTP_BAD_REQUEST, ['isMain' => 'Must be a boolean']);
        }

        $photos->updateIsMain($photo, $equipment, $parameters['isMain']);

        return $this->json($this->serializePhoto($photo));
    }

    private function checkPhotoBelongsTo(EquipmentPhoto $photo, Equipment $equipment): ?Response
    {
        if ($equipment->isDeleted()) {
            return new Response('Gerät nicht gefunden', Response::HTTP_NOT_FOUND);
        }

        $owner = $photo->getEquipment()?->getId();
        if ($owner === null || !$owner->equals($equipment->getId())) {
            return new Response('Foto gehört nicht zu diesem Gerät', Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_delete', requirements: ['equipmentId' => self::UUID, 'photoId' => self::UUID], methods: ['DELETE'])]
    public function deletePhoto(
        #[MapEntity(id: 'equipmentId')] Equipment  $equipment,
        #[MapEntity(id: 'photoId')] EquipmentPhoto $photo,
        PhotoStorage                               $storage,
        EquipmentPhotoRepository                   $photos,
        LoggerInterface                            $logger,
    ): Response
    {
        if ($notFound = $this->checkPhotoBelongsTo($photo, $equipment)) {
            return $notFound;
        }

        $id = $photo->getId();

        // Erst DB, dann Bucket: schlägt der Bucket fehl, bleibt nur eine unreferenzierte Datei.
        $photos->removeFromEquipment($photo, $equipment);

        try {
            $storage->deleteEquipment($id);
        } catch (Throwable $e) {
            $logger->error('Equipment-Foto konnte nicht aus dem Bucket gelöscht werden.', [
                'photoId' => $id->toRfc4122(),
                'exception' => $e,
            ]);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
