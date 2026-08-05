<?php

namespace App\Console\Commands;

use App\Support\MailTransportProbe;
use Illuminate\Console\Command;

class MailProbeCommand extends Command
{
    protected $signature = 'mgf:mail-probe';

    protected $description = 'Comprueba conectividad de correo (sin enviar)';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');

        if ($mailer === 'brevo') {
            $configured = filled(config('services.brevo.key'));
            $message = $configured
                ? 'Brevo API configurada (HTTPS — funciona en Railway).'
                : 'BREVO_API_KEY no configurada.';

            $this->line($message);

            return $configured ? self::SUCCESS : self::FAILURE;
        }

        if ($mailer === 'resend') {
            $configured = filled(config('services.resend.key'));
            $message = $configured
                ? 'Resend API configurada (HTTPS — funciona en Railway).'
                : 'RESEND_API_KEY no configurada.';

            $this->line($message);

            return $configured ? self::SUCCESS : self::FAILURE;
        }

        if ($this->isRailway() && $mailer === 'smtp') {
            $this->error('Railway bloquea SMTP saliente (puertos 465/587).');
            $this->warn('Solución: MAIL_MAILER=brevo + BREVO_API_KEY (ver docs/railway-mail-env.txt).');

            return self::FAILURE;
        }

        if ($mailer !== 'smtp') {
            $this->warn("MAIL_MAILER={$mailer}.");

            return self::SUCCESS;
        }

        $probe = MailTransportProbe::smtpReachable();
        $this->line($probe['message']);

        if (! $probe['ok'] && $this->isRailway()) {
            $this->warn('Usa Brevo en Railway: MAIL_MAILER=brevo y BREVO_API_KEY.');
        }

        return $probe['ok'] ? self::SUCCESS : self::FAILURE;
    }

    protected function isRailway(): bool
    {
        return filled(env('RAILWAY_ENVIRONMENT'))
            || filled(env('RAILWAY_PROJECT_ID'))
            || filled(env('RAILWAY_SERVICE_ID'));
    }
}
