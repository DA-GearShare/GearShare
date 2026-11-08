<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ConditionPhoto;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ConditionPhase;
use App\Repository\ConditionPhotoRepository;
use App\Security\Voter\ConditionPhotoVoter;
use App\Storage\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Zustandsfotos (sensibel). Sehen dürfen sie nur die Person, die sie hochgeladen hat,
 * und Admins. Die Regel steht in ConditionPhotoVoter (Einzelfoto) und
 * ConditionPhotoRepository::findVisibleFor() (Liste).
 *
 * Die Bytes fließen nie durch PHP: show() prüft nur die Berechtigung und antwortet mit
 * X-Accel-Redirect, nginx streamt das Bild dann aus der `internal`-Location in media.conf.
 */
#[Route('/api/v1', name: 'condition_photo_')]
final class ConditionPhotoController extends AbstractController
{
//    private const string UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
//    private const int MAX_BYTES = 15 * 1024 * 1024;
//
//    #[Route('/reservations/{reservationId}/condition-photos', name: 'upload', requirements: ['reservationId' => self::UUID], methods: ['POST'])]
//    public function upload(
//        #[MapEntity(id: 'reservationId')] Reservation $reservation,
//        #[CurrentUser] User                           $user,
//        Request                                       $request,
//        PhotoStorage                                  $storage,
//        EntityManagerInterface                        $em,
//    ): JsonResponse {
//        $this->denyAccessUnlessGranted(ConditionPhotoVoter::UPLOAD, $reservation);
//
//        $file = $request->files->get('file');
//        if (!$file instanceof UploadedFile) {
//            return $this->error('FILE_MISSING', 'Das Feld "file" fehlt.', Response::HTTP_BAD_REQUEST);
//        }
//        if (!$file->isValid()) {
//            // z. B. upload_max_filesize in PHP überschritten
//            return $this->error('UPLOAD_FAILED', $file->getErrorMessage(), Response::HTTP_BAD_REQUEST);
//        }
//        if ($file->getSize() > self::MAX_BYTES) {
//            return $this->error('FILE_TOO_LARGE', 'Maximal 15 MB pro Foto.', Response::HTTP_BAD_REQUEST);
//        }
//
//        $phase = ConditionPhase::tryFrom((string) $request->request->get('phase'));
//        if ($phase === null) {
//            return $this->error('INVALID_PHASE', 'phase muss "pickup" oder "return" sein.', Response::HTTP_BAD_REQUEST);
//        }
//
//        // ID selbst vergeben: DB-ID == Objekt-Key im Bucket.
//        $id = Uuid::v4();
//        $mime = $storage->putCondition($file, $id); // wirft 415 bei falschem Dateityp
//
//        try {
//            $photo = new ConditionPhoto($id, $reservation, $user, $phase, $mime);
//            $em->persist($photo);
//            $em->flush();
//        } catch (Throwable $e) {
//            // Kein verwaistes Objekt im Bucket zurücklassen.
//            try {
//                $storage->deleteCondition($id);
//            } catch (Throwable) {
//                // Original-Fehler ist wichtiger.
//            }
//            throw $e;
//        }
//
//        return $this->json($this->serialize($photo), Response::HTTP_CREATED, [
//            'Location' => $photo->getUrl(),
//        ]);
//    }
//
//    #[Route('/reservations/{reservationId}/condition-photos', name: 'list', requirements: ['reservationId' => self::UUID], methods: ['GET'])]
//    public function list(
//        #[MapEntity(id: 'reservationId')] Reservation $reservation,
//        #[CurrentUser] User                           $user,
//        ConditionPhotoRepository                      $photos,
//    ): JsonResponse {
//        // Admins bekommen alle Fotos, alle anderen nur ihre eigenen (ggf. eine leere Liste).
//        return $this->json(array_map(
//            $this->serialize(...),
//            $photos->findVisibleFor($reservation, $user),
//        ));
//    }
//
//    #[Route('/condition-photos/{id}', name: 'show', requirements: ['id' => self::UUID], methods: ['GET'])]
//    public function show(
//        #[MapEntity(id: 'id')] ConditionPhoto               $photo,
//        #[Autowire('%env(CONDITION_ACCEL_PREFIX)%')] string $accelPrefix,
//    ): Response {
//        $this->denyAccessUnlessGranted(ConditionPhotoVoter::VIEW, $photo);
//
//        $response = new Response('', Response::HTTP_OK, [
//            'X-Accel-Redirect' => $accelPrefix . '/' . $photo->getId()->toRfc4122(),
//            // Explizit setzen, sonst kann Symfonys Default text/html durchrutschen.
//            'Content-Type' => $photo->getMimeType(),
//        ]);
//
//        // Browser darf das Bild speichern, muss aber bei jeder Anzeige neu fragen.
//        // So greift die Berechtigungsprüfung auch nach Logout oder auf geteilten PCs.
//        $response->setPrivate();
//        $response->headers->addCacheControlDirective('no-cache');
//
//        return $response;
//    }
//
//    /** @return array<string, mixed> Form wie components.schemas.ConditionPhoto in api.yaml */
//    private function serialize(ConditionPhoto $photo): array
//    {
//        return [
//            'id' => $photo->getId()->toRfc4122(),
//            'reservationId' => $photo->getReservation()?->getId()?->toRfc4122(),
//            'takenByUserId' => $photo->getTakenBy()?->getUserId()?->toRfc4122(),
//            'phase' => $photo->getPhase()->value,
//            'url' => $photo->getUrl(),
//            'createdAt' => $photo->getCreatedAt()?->format(DATE_ATOM),
//        ];
//    }
//
//    private function error(string $code, string $message, int $status): JsonResponse
//    {
//        return new JsonResponse(['code' => $code, 'message' => $message], $status);
//    }
}
