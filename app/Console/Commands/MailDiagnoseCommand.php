<?php

namespace App\Console\Commands;

use App\Support\MailTransportProbe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailDiagnoseCommand extends Command
{
    protected $signature = 'mgf:mail-diagnose {--to= : Enviar correo de prueba a esta dirección}';

    protected $description = 'Diagnostica la configuración de correo y opcionalmente envía un correo de prueba';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $smtp = config('mail.mailers.smtp');

        $this->info('Configuración de correo');
        $rows = [
            ['APP_ENV', (string) config('app.env')],
            ['APP_URL', (string) config('app.url')],
            ['QUEUE_CONNECTION', (string) config('queue.default')],
            ['MAIL_MAILER', $mailer],
            ['MAIL_FROM', (string) config('mail.from.address')],
            ['MAIL_FROM_NAME', (string) config('mail.from.name')],
        ];

        if ($mailer === 'smtp') {
            $rows = array_merge($rows, [
                ['MAIL_SCHEME', (string) ($smtp['scheme'] ?? 'null')],
                ['MAIL_HOST', (string) ($smtp['host'] ?? '')],
                ['MAIL_PORT', (string) ($smtp['port'] ?? '')],
                ['MAIL_TIMEOUT', (string) ($smtp['timeout'] ?? '')],
                ['MAIL_USERNAME', (string) ($smtp['username'] ?? '')],
                ['MAIL_PASSWORD', $this->maskSecret((string) ($smtp['password'] ?? ''))],
            ]);
        }

        if ($mailer === 'resend') {
            $rows[] = ['RESEND_API_KEY', $this->maskSecret((string) config('services.resend.key'))];
        }

        $this->table(['Clave', 'Valor'], $rows);

        if ($mailer === 'smtp') {
            $probe = MailTransportProbe::smtpReachable();
            $probe['ok'] ? $this->info($probe['message']) : $this->error($probe['message']);

            if (! $probe['ok']) {
                $this->newLine();
                $this->warn('Railway y otros clouds suelen bloquear SMTP a Gmail (puerto 587).');
                $this->warn('Solución: usa Resend → MAIL_MAILER=resend y RESEND_API_KEY (ver docs/railway-mail-env.txt).');
                $this->warn('Alternativa: prueba MAIL_PORT=465 y MAIL_SCHEME=smtps.');

                return self::FAILURE;
            }

            if (blank($smtp['scheme'] ?? null)) {
                $this->error('MAIL_SCHEME está vacío. Usa MAIL_SCHEME=smtp.');

                return self::FAILURE;
            }
        }

        if ($mailer === 'resend' && blank(config('services.resend.key'))) {
            $this->error('RESEND_API_KEY no está configurada.');

            return self::FAILURE;
        }

        if (! in_array($mailer, ['smtp', 'resend'], true)) {
            $this->warn("MAIL_MAILER={$mailer}. Usa smtp (local) o resend (Railway/producción).");

            return self::FAILURE;
        }

        $to = $this->option('to') ?: (string) config('mail.from.address');

        $this->info("Enviando correo de prueba a {$to}...");

        try {
            Mail::raw(
                'Correo de prueba desde '.config('app.brand').' ('.now()->toDateTimeString().').',
                fn ($message) => $message
                    ->to($to)
                    ->subject('Prueba de correo — '.config('app.brand')),
            );

            $this->info('Correo enviado correctamente.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falló el envío: '.$exception->getMessage());
            Log::error('mgf:mail-diagnose failed', [
                'mailer' => $mailer,
                'exception' => $exception->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    protected function maskSecret(string $value): string
    {
        if ($value === '') {
            return '(vacío)';
        }

        if (strlen($value) <= 4) {
            return '****';
        }

        return substr($value, 0, 2).'****'.substr($value, -2);
    }
}
