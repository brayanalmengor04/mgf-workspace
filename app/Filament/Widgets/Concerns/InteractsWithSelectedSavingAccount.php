<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\SavingsAccount;
use App\Support\SavingsAccountSelection;
use Livewire\Attributes\On;

trait InteractsWithSelectedSavingAccount
{
    public ?int $selectedAccountId = null;

    public function bootInteractsWithSelectedSavingAccount(): void
    {
        $this->selectedAccountId = SavingsAccountSelection::id();
    }

    public function updatedSelectedAccountId(?int $accountId): void
    {
        SavingsAccountSelection::set($accountId);
        $this->dispatch('savings-account-selected', accountId: $accountId);
    }

    #[On('savings-account-selected')]
    public function refreshSelectedAccount(?int $accountId = null): void
    {
        if ($accountId !== null) {
            $this->selectedAccountId = $accountId;
            SavingsAccountSelection::set($accountId);

            return;
        }

        $this->selectedAccountId = SavingsAccountSelection::id();
    }

    protected function selectedSavingsAccount(): ?SavingsAccount
    {
        $accountId = $this->selectedAccountId ?? SavingsAccountSelection::id();

        if ($accountId === null) {
            return null;
        }

        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        return SavingsAccount::query()
            ->forUser($user)
            ->with(['transactions'])
            ->find($accountId);
    }

    /**
     * @return array<int, string>
     */
    protected function savingsAccountOptions(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return SavingsAccount::query()
            ->forUser($user)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
