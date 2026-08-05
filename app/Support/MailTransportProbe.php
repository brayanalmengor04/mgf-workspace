<?php

namespace App\Support;

class MailTransportProbe
{
    /**
     * @return array{ok: bool, message: string, port?: int}
     */
    public static function smtpReachable(?string $host = null, ?int $port = null, int $timeoutSeconds = 5): array
    {
        $host ??= (string) config('mail.mailers.smtp.host');
        $port ??= (int) config('mail.mailers.smtp.port', 587);

        if ($host === '' || $port <= 0) {
            return ['ok' => false, 'message' => 'MAIL_HOST o MAIL_PORT no configurados.'];
        }

        $result = self::tryTcp($host, $port, $timeoutSeconds);

        if ($result['ok']) {
            return $result;
        }

        if (GmailSmtpDefaults::isGmailHost($host) && $port === 587) {
            $fallback = self::tryTcp($host, 465, $timeoutSeconds);

            if ($fallback['ok']) {
                return [
                    'ok' => false,
                    'message' => 'Puerto 587 no responde pero 465 sí. Usa MAIL_PORT=465 y MAIL_SCHEME=smtps (como Nodemailer en tu portfolio).',
                    'port' => 465,
                ];
            }
        }

        return $result;
    }

    /**
     * @return array{ok: bool, message: string, port: int}
     */
    protected static function tryTcp(string $host, int $port, int $timeoutSeconds): array
    {
        $errno = 0;
        $errstr = '';
        $scheme = $port === 465 ? 'ssl' : 'tcp';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            return [
                'ok' => false,
                'message' => "No se puede conectar a {$host}:{$port} ({$errno}: {$errstr}).",
                'port' => $port,
            ];
        }

        fclose($socket);

        return [
            'ok' => true,
            'message' => "Conexión a {$host}:{$port} OK.",
            'port' => $port,
        ];
    }
}
