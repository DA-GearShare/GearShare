<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\EquipmentPhoto;
use App\Repository\EquipmentPhotoRepository;
use App\Repository\EquipmentRepository;
use App\Storage\PhotoStorage;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\JsonException;
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
            return new Response('Maximal 15 MB pro Foto', Response::HTTP_BAD_REQUEST);
        }

        // v4 = 122 Zufallsbits. Die UUID ist der einzige Schutz der öffentlichen URL.
        $id = Uuid::v4();
        $storage->putEquipment($file, $id); // wirft 415 bei falschem Dateityp

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
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_update', methods: ['PATCH'])]
    public function updatePhoto(
        Uuid                     $equipmentId,
        Uuid                     $photoId,
        Request                  $request,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        $photo = $photoRepository->find($photoId);

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

        $photoRepository->updateIsMain($photo, $equipment, $parameters['isMain']);

        return $this->json($this->serializePhoto($photo));
    }

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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{equipmentId}/photos/{photoId}', name: 'photo_delete', methods: ['DELETE'])]
    public function deletePhoto(
        Uuid                     $equipmentId,
        Uuid                     $photoId,
        EquipmentPhotoRepository $photoRepository,
        EquipmentRepository      $equipmentRepository,
        PhotoStorage             $storage,
        EquipmentPhotoRepository $photos,
        LoggerInterface          $logger,
    ): Response
    {
        $equipment = $equipmentRepository->find($equipmentId);
        $photo = $photoRepository->find($photoId);

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
