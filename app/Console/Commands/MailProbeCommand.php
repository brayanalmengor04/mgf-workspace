<?php

namespace App\Console\Commands;

use App\Support\MailTransportProbe;
use Illuminate\Console\Command;

class MailProbeCommand extends Command
{
    protected $signature = 'mgf:mail-probe';

    protected $description = 'Comprueba conectividad SMTP (sin enviar correo)';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');

        if ($mailer === 'resend') {
            $configured = filled(config('services.resend.key'));
            $message = $configured
                ? 'Resend API configurada (envío vía HTTPS, recomendado en Railway).'
                : 'RESEND_API_KEY no configurada.';

            $this->line($message);

            return $configured ? self::SUCCESS : self::FAILURE;
        }

        if ($mailer !== 'smtp') {
            $this->warn("MAIL_MAILER={$mailer}. No se probó conectividad SMTP.");

            return self::SUCCESS;
        }

        $probe = MailTransportProbe::smtpReachable();
        $this->line($probe['message']);

        if (! $probe['ok']) {
            $this->warn('Gmail: configura MAIL_PORT=465 y MAIL_SCHEME=smtps (igual que tu portfolio con Nodemailer).');
        }

        return $probe['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
