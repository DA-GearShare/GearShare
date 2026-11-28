<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    )
    {
    }

    public function sendTestEmail(string $toEmail, string $toName): string
    {
        $email = new TemplatedEmail()
            ->to(new Address($toEmail, $toName))
            ->subject('test!')
            ->htmlTemplate('emails/test.html.twig')
            ->textTemplate('emails/test.txt.twig')
            ->context([
                'name' => $toName,
            ]);

        return $this->send($email);
    }

    private function send(TemplatedEmail $email): string
    {
        try {
            $this->mailer->send($email);
            return "Success";
        } catch (TransportExceptionInterface $e) {
            return $e->getMessage();
        }
    }
}
