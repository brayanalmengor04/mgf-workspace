<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class UserInvitationService
{
    public static function generatePassword(): string
    {
        return Str::password(12, letters: true, numbers: true, symbols: false);
    }

    public static function regeneratePassword(User $user): string
    {
        $password = static::generatePassword();

        $user->forceFill([
            'password' => $password,
        ])->save();

        return $password;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function send(User $user, ?User $inviter = null, ?string $plainPassword = null, bool $forceResend = false): array
    {
        if ($plainPassword === null) {
            $plainPassword = static::regeneratePassword($user);
        }

        UserInvitationNotification::forInvitation(
            inviterName: $inviter?->name ?? (string) config('app.brand'),
            roleLabel: $user->role->label(),
            plainPassword: $plainPassword,
        );

        try {
            if ($forceResend) {
                static::clearResetToken($user);
            }

            $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
                ['email' => $user->email],
            );

            return match ($status) {
                Password::RESET_LINK_SENT => [
                    'success' => true,
                    'message' => "Se envió un correo a {$user->email} con las credenciales de acceso.",
                ],
                Password::RESET_THROTTLED => [
                    'success' => false,
                    'message' => 'Debes esperar al menos 1 minuto antes de volver a enviar la invitación.',
                ],
                Password::INVALID_USER => [
                    'success' => false,
                    'message' => 'No se encontró el usuario indicado.',
                ],
                default => [
                    'success' => false,
                    'message' => 'No se pudo enviar el correo. Verifica la configuración SMTP.',
                ],
            };
        } catch (Throwable $exception) {
            Log::error('Error al enviar invitación por correo.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            return [
                'success' => false,
                'message' => config('app.debug')
                    ? 'Error SMTP: '.$exception->getMessage()
                    : 'No se pudo enviar el correo. Verifica la configuración SMTP.',
            ];
        } finally {
            UserInvitationNotification::clearContext();
        }
    }

    protected static function clearResetToken(User $user): void
    {
        $table = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.table', 'password_reset_tokens');

        DB::table($table)
            ->where('email', $user->email)
            ->delete();
    }
}
