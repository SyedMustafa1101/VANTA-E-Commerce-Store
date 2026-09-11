<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer as PHPMailerClient;

final class SmtpMailTransport implements MailTransportInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    /** @param array<string, string> $message */
    public function send(array $message): void
    {
        if (($this->config['enabled'] ?? false) !== true) {
            throw new RuntimeException('SMTP delivery is not enabled.');
        }
        if (!class_exists(PHPMailerClient::class)) {
            throw new RuntimeException('PHPMailer is not installed. Run Composer install.');
        }

        $host = trim((string) ($this->config['host'] ?? ''));
        $fromEmail = trim((string) ($this->config['from_email'] ?? ''));
        $fromName = trim((string) ($this->config['from_name'] ?? 'VANTA'));
        $port = (int) ($this->config['port'] ?? 0);
        $encryption = strtolower(trim((string) ($this->config['encryption'] ?? 'tls')));
        if (
            $host === ''
            || $port < 1
            || $port > 65535
            || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)
            || !filter_var($message['to_email'] ?? '', FILTER_VALIDATE_EMAIL)
            || !in_array($encryption, ['tls', 'ssl', 'none'], true)
        ) {
            throw new RuntimeException('SMTP mail configuration is incomplete.');
        }

        $mailer = new PHPMailerClient(true);
        $mailer->isSMTP();
        $mailer->SMTPDebug = 0;
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->Timeout = max(1, min((int) ($this->config['timeout'] ?? 15), 60));
        $mailer->SMTPAuth = (bool) ($this->config['auth'] ?? true);
        $mailer->Username = (string) ($this->config['username'] ?? '');
        $mailer->Password = (string) ($this->config['password'] ?? '');
        if ($encryption === 'tls') {
            $mailer->SMTPSecure = PHPMailerClient::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailerClient::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = '';
            $mailer->SMTPAutoTLS = false;
        }

        $mailer->CharSet = PHPMailerClient::CHARSET_UTF8;
        $mailer->Encoding = PHPMailerClient::ENCODING_BASE64;
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($message['to_email'], $message['to_name']);

        $replyToEmail = trim((string) ($this->config['reply_to_email'] ?? ''));
        if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mailer->addReplyTo($replyToEmail, trim((string) ($this->config['reply_to_name'] ?? '')));
        }

        $mailer->isHTML(true);
        $mailer->Subject = $message['subject'];
        $mailer->Body = $message['html'];
        $mailer->AltBody = $message['text'];
        $mailer->send();
    }
}
