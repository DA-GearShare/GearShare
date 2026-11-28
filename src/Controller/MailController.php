<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mailer\EmailService;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/mail', name: 'mail_')]
final class MailController extends AbstractController
{
    private const int MAX_NAME_LENGTH = 100;

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(
        EmailService $mailService,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        Request $request,
    ): Response {
        try {
            $parameters = json_decode($request->getContent(), true, 8, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['message' => 'Malformed JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($parameters) || ($parameters !== [] && array_is_list($parameters))) {
            return new JsonResponse(['message' => 'Body muss ein JSON Objekt sein'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];

        $email = $parameters['email'] ?? null;
        if ($email === null || $email === '') {
            $errors['email'] = 'Das Feld "email" fehlt';
        } elseif (!\is_string($email)) {
            $errors['email'] = 'Das Feld "email" muss ein String sein';
        } elseif (\count($validator->validate($email, new Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT))) > 0) {
            $errors['email'] = \sprintf('"%s" ist keine gültige E-Mail-Adresse', $email);
        }

        $name = $parameters['name'] ?? null;
        if ($name !== null && !\is_string($name)) {
            $errors['name'] = 'Das Feld "name" muss ein String sein';
        } elseif (\is_string($name) && mb_strlen($name) > self::MAX_NAME_LENGTH) {
            $errors['name'] = \sprintf('Das Feld "name" darf maximal %d Zeichen lang sein', self::MAX_NAME_LENGTH);
        }

        if ($errors !== []) {
            return new JsonResponse(['message' => 'Invalider Request Body', 'details' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) $name);
        if ($name === '') {
            $name = $email;
        }

        try {
            $mailService->sendTestEmail($email, $name);
        } catch (TransportExceptionInterface $e) {
            $logger->error('Test-E-Mail konnte nicht zugestellt werden', ['exception' => $e, 'to' => $email]);

            return new JsonResponse(
                ['message' => 'Test-E-Mail konnte nicht zugestellt werden', 'details' => ['transport' => $e->getMessage()]],
                Response::HTTP_BAD_GATEWAY,
            );
        } catch (MessengerExceptionInterface $e) {
            $logger->error('Test-E-Mail konnte nicht in die Warteschlange gestellt werden', ['exception' => $e, 'to' => $email]);

            return new JsonResponse(
                ['message' => 'Test-E-Mail konnte nicht in die Warteschlange gestellt werden', 'details' => ['queue' => $e->getMessage()]],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return new JsonResponse(
            ['message' => 'Test-E-Mail wurde in die Warteschlange gestellt', 'email' => $email],
            Response::HTTP_ACCEPTED,
        );
    }
}
