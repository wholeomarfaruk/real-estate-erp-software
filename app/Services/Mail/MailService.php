<?php

namespace App\Services\Mail;

use App\Models\SmtpConfiguration;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class MailService
{
    /**
     * Send an HTML email using the SmtpConfiguration stored in the database.
     *
     * Deliberately avoids config/mail.php and env vars so that the admin-configured
     * SMTP credentials are always used regardless of server environment.
     *
     * @throws \RuntimeException if no SMTP config exists or sending fails
     */
    public function send(string $to, string $subject, string $htmlBody): void
    {
        $config = SmtpConfiguration::first();

        if (! $config) {
            throw new \RuntimeException('SMTP is not configured. Go to Settings → SMTP Config and save your mail server credentials.');
        }

        $password = Crypt::decryptString($config->password);

        $transport = new EsmtpTransport(
            $config->host,
            $config->port,
            $config->encryption === 'ssl',
        );
        $transport->setUsername($config->username);
        $transport->setPassword($password);

        $mailer = new SymfonyMailer($transport);

        $email = (new Email())
            ->from(new Address($config->from_address, $config->from_name))
            ->to($to)
            ->subject($subject)
            ->html($htmlBody);

        $mailer->send($email);
    }
}
