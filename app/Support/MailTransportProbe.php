<?php

namespace App\Support;

class MailTransportProbe
{
    /**
     * @return array{ok: bool, message: string}
     */
    public static function smtpReachable(?string $host = null, ?int $port = null, int $timeoutSeconds = 5): array
    {
        $host ??= (string) config('mail.mailers.smtp.host');
        $port ??= (int) config('mail.mailers.smtp.port', 587);

        if ($host === '' || $port <= 0) {
            return ['ok' => false, 'message' => 'MAIL_HOST o MAIL_PORT no configurados.'];
        }

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            return [
                'ok' => false,
                'message' => "No se puede conectar a {$host}:{$port} ({$errno}: {$errstr}).",
            ];
        }

        fclose($socket);

        return ['ok' => true, 'message' => "Conexión TCP a {$host}:{$port} OK."];
    }

    public static function usesResend(): bool
    {
        return filled(config('services.resend.key'))
            && (string) config('mail.default') === 'resend';
    }
}
