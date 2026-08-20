<?php

namespace App\Filament\Resources\SavingAccounts\Pages;

use App\Filament\Resources\SavingAccounts\SavingAccountResource;
use App\Services\Savings\SavingsLedgerService;
use Filament\Resources\Pages\CreateRecord;

class CreateSavingAccount extends CreateRecord
{
    protected static string $resource = SavingAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['current_balance'] = 0;
        $data['pending_replenishment'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $openingBalance = (float) ($this->form->getRawState()['opening_balance'] ?? 0);

        if ($openingBalance <= 0) {
            return;
        }

        app(SavingsLedgerService::class)->recordOpening(
            account: $this->record,
            amount: $openingBalance,
            notes: 'Saldo inicial',
        );
    }
}
