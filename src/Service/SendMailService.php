<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

readonly class SendMailService
{
    public function __construct(
        #[Autowire('%env(MAIL_FROM)%')]
        private string $fromEmail
    )
    {
    }

    public function sendMail(string $toEmail, string $subject, string $template): string
    {
        $messageBus = new MessageBus;
        try {
            $email = new Email()
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject($subject)
                ->text('Test');

            print_r($email);
            $messageBus->dispatch($email);
        } catch (ExceptionInterface $e) {
            return $e->getMessage();
        }
        return "Successfully sent email!";
    }
}
