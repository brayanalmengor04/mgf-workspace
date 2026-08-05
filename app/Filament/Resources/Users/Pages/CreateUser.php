<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserInvitationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $plainPassword = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainPassword = UserInvitationService::generatePassword();
        $data['password'] = $this->plainPassword;

        return $data;
    }

    protected function afterCreate(): void
    {
        $result = UserInvitationService::send(
            $this->record,
            auth()->user(),
            plainPassword: $this->plainPassword,
            forceResend: true,
        );

        if ($result['success']) {
            Notification::make()
                ->title('Invitación enviada')
                ->body($result['message'])
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Usuario creado')
            ->body($result['message'].' Puedes reenviarla desde la edición del usuario.')
            ->warning()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
