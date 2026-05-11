<?php

declare(strict_types=1);

final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $replyTo = ''): array
    {
        $transport = Settings::get('mail_transport', 'mail');

        try {
            if ($transport === 'smtp') {
                self::sendSmtp($to, $subject, $html, $replyTo);
                return [true, ''];
            }

            if (self::sendNative($to, $subject, $html, $replyTo)) {
                return [true, ''];
            }

            return [false, 'PHP mail() returned false. Use SMTP settings if your host does not support native mail.'];
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    private static function sendNative(string $to, string $subject, string $html, string $replyTo): bool
    {
        $headers = self::headers($replyTo);

        return mail(
            self::cleanHeader($to),
            self::cleanHeader($subject),
            $html,
            implode("\r\n", $headers)
        );
    }

    private static function sendSmtp(string $to, string $subject, string $html, string $replyTo): void
    {
        $host = Settings::get('smtp_host');
        $port = (int)Settings::get('smtp_port', '587');
        $encryption = Settings::get('smtp_encryption', 'tls');
        $username = Settings::get('smtp_username');
        $password = Settings::get('smtp_password');

        if ($host === '') {
            throw new RuntimeException('SMTP host is required.');
        }

        $socketHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = stream_socket_client($socketHost . ':' . $port, $errno, $errstr, 20);

        if (!$socket) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr);
        }

        stream_set_timeout($socket, 20);

        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO ' . self::serverName(), [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP STARTTLS negotiation failed.');
                }
                self::command($socket, 'EHLO ' . self::serverName(), [250]);
            }

            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode($password), [235]);
            }

            $fromEmail = self::fromEmail();
            self::command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            self::command($socket, 'RCPT TO:<' . self::cleanHeader($to) . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $message = self::message($to, $subject, $html, $replyTo);
            fwrite($socket, self::dotStuff($message) . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private static function message(string $to, string $subject, string $html, string $replyTo): string
    {
        $headers = array_merge([
            'To: ' . self::cleanHeader($to),
            'Subject: ' . self::cleanHeader($subject),
            'Date: ' . date(DATE_RFC2822),
        ], self::headers($replyTo));

        return implode("\r\n", $headers) . "\r\n\r\n" . $html;
    }

    private static function headers(string $replyTo): array
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::fromName() . ' <' . self::fromEmail() . '>',
        ];

        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . self::cleanHeader($replyTo);
        }

        return $headers;
    }

    private static function command($socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    private static function expect($socket, array $expected): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }

        return $response;
    }

    private static function dotStuff(string $message): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $message);
        $stuffed = preg_replace('/^\./m', '..', $normalized) ?: $normalized;

        return str_replace("\n", "\r\n", $stuffed);
    }

    private static function cleanHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private static function fromName(): string
    {
        return self::cleanHeader(Settings::get('mail_from_name', 'Holy Cross Parish and Friary'));
    }

    private static function fromEmail(): string
    {
        $email = Settings::get('mail_from_email');

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return 'no-reply@' . self::serverName();
    }

    private static function serverName(): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return preg_replace('/[^a-zA-Z0-9.-]/', '', $host) ?: 'localhost';
    }
}
