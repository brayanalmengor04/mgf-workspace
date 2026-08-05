<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserInvitationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendInvitation')
                ->label('Reenviar invitación')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reenviar invitación')
                ->modalDescription('Se enviará un nuevo correo con un enlace para que el usuario active su cuenta.')
                ->action(function (User $record): void {
                    $result = UserInvitationService::send($record, auth()->user(), forceResend: true);

                    Notification::make()
                        ->title($result['success'] ? 'Invitación reenviada' : 'No se pudo enviar el correo')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                }),
            DeleteAction::make()
                ->visible(fn (User $record): bool => $record->id !== auth()->id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
