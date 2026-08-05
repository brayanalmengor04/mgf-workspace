<?php

namespace App\Filament\Support;

use App\Support\WhatsAppRedirect;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DocumentShareActions
{
    /**
     * @param  Closure(Model, string, mixed): void  $sendEmail
     * @param  Closure(Model): void|null  $prepareRecord
     */
    public static function email(string $name, Closure $sendEmail, ?Closure $prepareRecord = null, ?Closure $visible = null): Action
    {
        return Action::make($name)
            ->label('Correo electrónico')
            ->icon('heroicon-o-envelope')
            ->form([
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->placeholder('ejemplo@correo.com'),
            ])
            ->fillForm(fn (Model $record): array => [
                'email' => filled($record->recipient_email ?? null) ? $record->recipient_email : '',
            ])
            ->visible($visible ?? fn (): bool => true)
            ->action(function (Model $record, array $data, Action $action) use ($sendEmail, $prepareRecord): void {
                $user = auth()->user();
                if ($user === null) {
                    return;
                }

                if ($prepareRecord !== null) {
                    $prepareRecord($record);
                    $record->refresh();
                }

                try {
                    $sendEmail($record, $data['email'], $user);

                    Notification::make()
                        ->title('Documento enviado')
                        ->body("Se envió a {$data['email']} con el PDF adjunto.")
                        ->success()
                        ->send();
                } catch (\Throwable) {
                    Notification::make()
                        ->title('No se pudo enviar')
                        ->body('Verifica la configuración SMTP.')
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * @param  Closure(Model, string, mixed): array{web: string, app: string, pdf_url: string, filename: string, text: string}  $buildWhatsAppLinks
     * @param  Closure(Model): void|null  $prepareRecord
     */
    public static function whatsApp(string $name, Closure $buildWhatsAppLinks, ?Closure $prepareRecord = null, ?Closure $visible = null): Action
    {
        return Action::make($name)
            ->label('WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->modal(fn (Model $record): bool => blank(self::recordPhone($record)))
            ->form(fn (Model $record): array => blank(self::recordPhone($record))
                ? [
                    TextInput::make('phone')
                        ->label('Número de WhatsApp')
                        ->required()
                        ->prefix('+507')
                        ->placeholder('6542-5xxx')
                        ->helperText('Solo números de Panamá (+507).'),
                ]
                : [])
            ->fillForm(fn (Model $record): array => [
                'phone' => self::recordPhone($record) ?? '',
            ])
            ->visible($visible ?? fn (): bool => true)
            ->action(function (Model $record, array $data, Action $action) use ($buildWhatsAppLinks, $prepareRecord): void {
                $user = auth()->user();
                if ($user === null) {
                    return;
                }

                if ($prepareRecord !== null) {
                    $prepareRecord($record);
                    $record->refresh();
                }

                $phone = filled($data['phone'] ?? null)
                    ? $data['phone']
                    : self::recordPhone($record);

                if (blank($phone)) {
                    Notification::make()
                        ->title('Falta el número')
                        ->body('Agrega un teléfono del destinatario o escríbelo en el formulario.')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    $links = $buildWhatsAppLinks($record, $phone, $user);

                    Notification::make()
                        ->title('PDF listo')
                        ->body('En móvil se adjuntará el PDF. En escritorio, el mensaje incluye el enlace de descarga.')
                        ->success()
                        ->send();

                    $action->getLivewire()->js(
                        WhatsAppRedirect::shareDocumentScript($links),
                    );
                } catch (\Throwable) {
                    Notification::make()
                        ->title('No se pudo preparar el PDF')
                        ->body('Guarda el documento e inténtalo de nuevo.')
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function recordPhone(Model $record): ?string
    {
        $phone = $record->recipient_phone ?? null;

        return filled($phone) ? (string) $phone : null;
    }
}
