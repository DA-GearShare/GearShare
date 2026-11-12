<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\EquipmentPhoto;
use App\Repository\EquipmentPhotoRepository;
use App\Repository\EquipmentRepository;
use App\Service\EquipmentPhotoService;
use App\Storage\PhotoStorage;
use JsonException;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[Route('/api/v1/equipment', name: 'equipment_photo')]
final class EquipmentPhotoController extends AbstractController
{
    private const int MAX_PHOTO_BYTES = 15 * 1024 * 1024;

    public function __construct(
        #[Autowire('%env(MEDIA_BASE_URL)%')] private readonly string $mediaBaseUrl,
    )
    {
    }

    /**
     * @param Uuid $equipmentId
     * @param EquipmentPhotoRepository $photoRepository
     * @param EquipmentRepository $equipmentRepository
     * @return Response
     */
    #[Route('/{equipmentId}/photos', name: 'photo_list', methods: ['GET'])]
    public function listPhotos(
        Uuid                     $equipmentId,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        return $this->json(array_map(
            $this->serializePhoto(...),
            $photoRepository->findForEquipment($equipment),
        ));
    }


    /**
     * @throws FilesystemException
     * @throws Throwable
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos', name: 'photo_upload', methods: ['POST'])]
    public function uploadPhoto(
        Uuid                     $equipmentId,
        Request                  $request,
        PhotoStorage             $storage,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new Response('Das Feld "file" fehlt', Response::HTTP_BAD_REQUEST);
        }
        if (!$file->isValid()) {
            return new Response($file->getErrorMessage(), Response::HTTP_BAD_REQUEST);
        }
        if ($file->getSize() > self::MAX_PHOTO_BYTES) {
            return new Response(sprintf('Maximal %s MB pro Foto', self::MAX_PHOTO_BYTES / (1024 * 1024)), Response::HTTP_BAD_REQUEST);
        }

        $id = Uuid::v4();
        $storage->putEquipment($file, $id); // throws 415 when wrong filetype

        try {
            $photo = $photoRepository->createForEquipment(
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

    /**
     * @param EquipmentPhoto $photo
     * @return array
     */
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

    /**
     * @param Uuid $equipmentId
     * @param Uuid $photoId
     * @param Request $request
     * @param EquipmentPhotoRepository $photoRepository
     * @param EquipmentRepository $equipmentRepository
     * @return Response
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_mark_isMain', methods: ['PATCH'])]
    public function markAsMainPhoto(
        Uuid                     $equipmentId,
        Uuid                     $photoId,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        $photo = $photoRepository->find($photoId);

        if ($notFound = $this->checkPhotoBelongsTo($photo, $equipment)) {
            return $notFound;
        }

        $photoRepository->markAsIsMain($photo, $equipment);

        return $this->json($this->serializePhoto($photo));
    }

    /**
     * @param EquipmentPhoto|null $photo
     * @param Equipment|null $equipment
     * @return Response|null
     */
    private function checkPhotoBelongsTo(?EquipmentPhoto $photo, ?Equipment $equipment): ?Response
    {

        if ($equipment === null || $equipment->isDeleted()) {
            return new Response('Equipment nicht gefunden.', Response::HTTP_NOT_FOUND);
        }
        if ($photo === null) {
            return new Response('Photo nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $owner = $photo->getEquipment()?->getId();
        if ($owner === null || !$owner->equals($equipment->getId())) {
            return new Response('Foto gehört nicht zu diesem Gerät', Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    /**
     * @param Uuid $equipmentId
     * @param Uuid $photoId
     * @param EquipmentPhotoRepository $photoRepository
     * @param EquipmentRepository $equipmentRepository
     * @param EquipmentPhotoService $photoService
     * @return Response
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_delete', methods: ['DELETE'])]
    public function deletePhoto(
        Uuid                     $equipmentId,
        Uuid                     $photoId,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
        EquipmentPhotoService    $photoService,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        $photo = $photoRepository->find($photoId);

        if ($notFound = $this->checkPhotoBelongsTo($photo, $equipment)) {
            return $notFound;
        }

        $photoService->delete($photo, $equipment);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
