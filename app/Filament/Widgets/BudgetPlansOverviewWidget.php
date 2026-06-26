<?php

namespace App\Filament\Widgets;

use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BudgetPlansOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Mis presupuestos';

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $query = BudgetPlan::query()->forUser($user);

        $latest = (clone $query)
            ->where('status', BudgetStatus::Issued)
            ->latest('issued_at')
            ->first();

        $draftCount = (clone $query)->where('status', BudgetStatus::Draft)->count();
        $issuedCount = (clone $query)->where('status', BudgetStatus::Issued)->count();

        $stats = [
            Stat::make('Emitidos', (string) $issuedCount)
                ->description('Presupuestos generados')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),
            Stat::make('Borradores', (string) $draftCount)
                ->description('Pendientes de PDF')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
        ];

        if ($latest !== null) {
            $remaining = (float) $latest->remaining_balance;
            $stats[] = Stat::make('Último disponible', MoneyFormatter::format($remaining, $latest->currency))
                ->description($latest->title.' · '.QuoteCurrency::resolve($latest->currency)->label())
                ->descriptionIcon($remaining >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($remaining >= 0 ? 'success' : 'danger');
        }

        return $stats;
    }
}
