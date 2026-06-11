<?php

declare(strict_types=1);

namespace App\Service;

use App\Email\EmailBuilderInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $noReplyEmail,
        private string $fromName,
    ) {
    }

    public function send(EmailBuilderInterface $emailBuilder, array $payload): void
    {
        $email = $this->createEmail($emailBuilder, $payload);
        $this->sendEmail($email);
    }

    public function createEmail(EmailBuilderInterface $emailBuilder, array $payload): Email
    {
        return $emailBuilder
            ->build($payload)
            ->from(new Address($this->noReplyEmail, $this->fromName));
    }

    public function sendEmail(Email $email): void
    {
        $this->mailer->send($email);
    }
}
