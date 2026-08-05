<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class UserInvitationNotification extends ResetPassword
{
    protected static ?string $inviterName = null;

    protected static ?string $roleLabel = null;

    protected static ?string $plainPassword = null;

    protected static bool $isInvitation = false;

    public static function forInvitation(string $inviterName, string $roleLabel, string $plainPassword): void
    {
        static::$inviterName = $inviterName;
        static::$roleLabel = $roleLabel;
        static::$plainPassword = $plainPassword;
        static::$isInvitation = true;
    }

    public static function clearContext(): void
    {
        static::$inviterName = null;
        static::$roleLabel = null;
        static::$plainPassword = null;
        static::$isInvitation = false;
    }

    protected function resetUrl(mixed $notifiable): string
    {
        return Filament::getResetPasswordUrl($this->token, $notifiable);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $appBrand = (string) config('app.brand');
        $loginUrl = Filament::getLoginUrl();
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name', $appBrand);

        if (static::$isInvitation) {
            return (new MailMessage)
                ->from($fromAddress, $fromName)
                ->subject("Bienvenido/a a {$appBrand} — tus credenciales de acceso")
                ->view('mail.user-invitation', [
                    'url' => $url,
                    'loginUrl' => $loginUrl,
                    'userName' => $notifiable->name,
                    'userEmail' => $notifiable->email,
                    'plainPassword' => static::$plainPassword,
                    'inviterName' => static::$inviterName ?? $appBrand,
                    'roleLabel' => static::$roleLabel ?? 'Usuario',
                    'appBrand' => $appBrand,
                    'expireMinutes' => $expireMinutes,
                ]);
        }

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("Restablecer contraseña — {$appBrand}")
            ->view('mail.password-reset', [
                'url' => $url,
                'userName' => $notifiable->name,
                'appBrand' => $appBrand,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
