<?php

namespace App\Filament\Widgets;

class SavingsActivityTrendChartWidget extends SavingsTrendChartWidget
{
    protected ?string $heading = 'Tendencia de la cuenta';

    protected static ?int $sort = 10;

    #[\Livewire\Attributes\On('savings-account-selected')]
    public function refreshTrendChart(?int $accountId = null): void
    {
        if ($accountId !== null) {
            $this->selectedAccountId = $accountId;
        }

        $this->cachedData = null;
    }
}
