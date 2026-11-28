<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Mime\Address;

readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface when sent synchronously and delivery fails
     * @throws ExceptionInterface when the message cannot be queued
     */
    public function sendTestEmail(string $toEmail, string $toName): void
    {
        $email = new TemplatedEmail()
            ->to(new Address($toEmail, $toName))
            ->subject('GearShare - Test Email')
            ->htmlTemplate('emails/test.html.twig')
            ->textTemplate('emails/test.txt.twig')
            ->context([
                'name' => $toName,
            ]);

        $this->mailer->send($email);
    }
}
