<?php

namespace App\Filament\Widgets\Concerns;

use App\Enums\SavingsProjectionCadence;
use App\Support\SavingsProjectionCadenceSelection;
use Livewire\Attributes\On;

trait InteractsWithSavingsProjectionCadence
{
    public string $projectionCadence = 'biweekly';

    public function bootInteractsWithSavingsProjectionCadence(): void
    {
        $this->projectionCadence = SavingsProjectionCadenceSelection::get()->value;
    }

    public function updatedProjectionCadence(string $cadence): void
    {
        SavingsProjectionCadenceSelection::set($cadence);
        $this->dispatch('savings-projection-cadence-changed', cadence: $cadence);
    }

    #[On('savings-projection-cadence-changed')]
    public function refreshProjectionCadence(?string $cadence = null): void
    {
        if ($cadence !== null) {
            $this->projectionCadence = $cadence;
            SavingsProjectionCadenceSelection::set($cadence);

            return;
        }

        $this->projectionCadence = SavingsProjectionCadenceSelection::get()->value;
    }

    protected function selectedProjectionCadence(): SavingsProjectionCadence
    {
        return SavingsProjectionCadence::tryFrom($this->projectionCadence)
            ?? SavingsProjectionCadenceSelection::get();
    }

    /**
     * @return array<string, string>
     */
    protected function projectionCadenceOptions(): array
    {
        return SavingsProjectionCadence::options();
    }
}
