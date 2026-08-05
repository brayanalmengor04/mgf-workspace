<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailDiagnoseCommand extends Command
{
    protected $signature = 'mgf:mail-diagnose {--to= : Enviar correo de prueba a esta dirección}';

    protected $description = 'Diagnostica la configuración SMTP y opcionalmente envía un correo de prueba';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $smtp = config('mail.mailers.smtp');

        $this->info('Configuración de correo');
        $this->table(
            ['Clave', 'Valor'],
            [
                ['APP_ENV', (string) config('app.env')],
                ['APP_URL', (string) config('app.url')],
                ['QUEUE_CONNECTION', (string) config('queue.default')],
                ['MAIL_MAILER', $mailer],
                ['MAIL_SCHEME', (string) ($smtp['scheme'] ?? 'null')],
                ['MAIL_HOST', (string) ($smtp['host'] ?? '')],
                ['MAIL_PORT', (string) ($smtp['port'] ?? '')],
                ['MAIL_USERNAME', (string) ($smtp['username'] ?? '')],
                ['MAIL_PASSWORD', $this->maskSecret((string) ($smtp['password'] ?? ''))],
                ['MAIL_FROM', (string) config('mail.from.address')],
                ['MAIL_FROM_NAME', (string) config('mail.from.name')],
            ],
        );

        if ($mailer !== 'smtp') {
            $this->warn("MAIL_MAILER no es smtp (actual: {$mailer}). Los correos no saldrán por Gmail.");

            return self::FAILURE;
        }

        if (blank($smtp['scheme'] ?? null)) {
            $this->error('MAIL_SCHEME está vacío. En Railway usa MAIL_SCHEME=smtp (no MAIL_ENCRYPTION).');

            return self::FAILURE;
        }

        if (config('queue.default') === 'database') {
            $this->warn('QUEUE_CONNECTION=database: los correos en cola requieren `php artisan queue:work`.');
        }

        $to = $this->option('to') ?: (string) config('mail.from.address');

        $this->info("Enviando correo de prueba a {$to}...");

        try {
            Mail::raw(
                'Correo de prueba desde '.config('app.brand').' ('.now()->toDateTimeString().').',
                fn ($message) => $message
                    ->to($to)
                    ->subject('Prueba SMTP — '.config('app.brand')),
            );

            $this->info('Correo enviado correctamente.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falló el envío: '.$exception->getMessage());
            Log::error('mgf:mail-diagnose failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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
