<?php

namespace App\Support;

class GmailSmtpDefaults
{
    public static function isGmailHost(?string $host): bool
    {
        return str_contains(strtolower((string) $host), 'gmail.com');
    }

    /**
     * Nodemailer `service: 'gmail'` usa puerto 465 + SSL (smtps).
     * @see https://github.com/brayanalmengor04/brayanalmengor04.github.io/blob/main/src/pages/api/send-email.js
     */
    public static function port(?string $host, mixed $configuredPort): int
    {
        if (self::isGmailHost($host)) {
            return 465;
        }

        if ($configuredPort !== null && $configuredPort !== '') {
            return (int) $configuredPort;
        }

        return 587;
    }

    public static function scheme(?string $host, ?string $configuredScheme, int $port, ?string $encryption): ?string
    {
        if (self::isGmailHost($host)) {
            return 'smtps';
        }

        if (filled($configuredScheme)) {
            return $configuredScheme;
        }

        if (filled($encryption)) {
            return match (strtolower($encryption)) {
                'tls', 'starttls' => 'smtp',
                'ssl' => 'smtps',
                default => null,
            };
        }

        return $port === 465 ? 'smtps' : 'smtp';
    }
}
