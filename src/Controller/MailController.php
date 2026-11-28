<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mailer\EmailService;
use Exception;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerExceptionInterface;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/mail', name: 'mail_')]
final class MailController extends AbstractController
{
    private const int MAX_NAME_LENGTH = 100;
    private const int DEFAULT_FAILED_LIMIT = 50;
    private const int MAX_FAILED_LIMIT = 200;

    /**
     * Shows whether outgoing mail is healthy: how many mails are still waiting
     * in the queue and which ones finally failed (all retries exhausted) and
     * now sit in the Messenger failure transport.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(
        #[Autowire(service: 'messenger.transport.email')]
        TransportInterface $emailTransport,
        #[Autowire(service: 'messenger.transport.failed')]
        TransportInterface $failedTransport,
        LoggerInterface $logger,
        Request $request,
    ): Response {
        $limit = $request->query->getString('limit', (string) self::DEFAULT_FAILED_LIMIT);
        if (!ctype_digit($limit) || (int) $limit < 1 || (int) $limit > self::MAX_FAILED_LIMIT) {
            return new JsonResponse(
                [
                    'message' => 'Invalide Query-Parameter',
                    'details' => ['limit' => \sprintf('"limit" muss eine Ganzzahl zwischen 1 und %d sein', self::MAX_FAILED_LIMIT)],
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $pending = $emailTransport instanceof MessageCountAwareInterface ? $emailTransport->getMessageCount() : null;
            $failedCount = $failedTransport instanceof MessageCountAwareInterface ? $failedTransport->getMessageCount() : null;
            $failed = [];
            if ($failedTransport instanceof ListableReceiverInterface) {
                foreach ($failedTransport->all((int) $limit) as $envelope) {
                    $failed[] = $this->describeFailedEnvelope($envelope);
                }
            }
        } catch (Exception $e) {
            $logger->error('E-Mail-Status konnte nicht ermittelt werden', ['exception' => $e]);

            return new JsonResponse(
                ['message' => 'E-Mail-Warteschlange nicht erreichbar', 'details' => ['queue' => $e->getMessage()]],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return new JsonResponse([
            'healthy' => $failedCount === null ? null : $failedCount === 0,
            'pending' => $pending,
            'failedCount' => $failedCount,
            'failed' => $failed,
        ]);
    }

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

    /**
     * @return array<string, mixed>
     */
    private function describeFailedEnvelope(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        $error = $envelope->last(ErrorDetailsStamp::class);
        $failedAt = $envelope->last(RedeliveryStamp::class)?->getRedeliveredAt();

        $subject = null;
        $recipients = [];
        if ($message instanceof SendEmailMessage) {
            $email = $message->getMessage();
            if ($email instanceof Email) {
                $subject = $email->getSubject();
                $recipients = array_map(static fn (Address $a): string => $a->getAddress(), $email->getTo());
            }
            if ($message->getEnvelope() !== null) {
                $recipients = array_map(static fn (Address $a): string => $a->getAddress(), $message->getEnvelope()->getRecipients());
            }
        }

        if ($message instanceof MessageDecodingFailedException) {
            $error ??= ErrorDetailsStamp::create($message);
        }

        return [
            'id' => (string) $envelope->last(TransportMessageIdStamp::class)?->getId(),
            'type' => $message::class,
            'subject' => $subject,
            'recipients' => array_values(array_unique($recipients)),
            'failedAt' => $failedAt?->format(\DATE_ATOM),
            'error' => $error === null ? null : [
                'class' => $error->getExceptionClass(),
                'code' => $error->getExceptionCode(),
                'message' => $error->getExceptionMessage(),
            ],
        ];
    }
}
