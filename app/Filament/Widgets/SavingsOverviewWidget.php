<?php

namespace App\Filament\Widgets;

use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SavingsOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Mis ahorros';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $summary = app(SavingsLedgerService::class)->summaryForUser($user);

        return [
            Stat::make('Saldo total', MoneyFormatter::format($summary['total_balance']))
                ->description($summary['active_accounts'].' cuenta(s) activa(s)')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('success'),
            Stat::make('Por reponer', MoneyFormatter::format($summary['pending_replenishment']))
                ->description($summary['pending_replenishment'] > 0
                    ? 'Retiros pendientes de reposición'
                    : 'Sin retiros pendientes')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($summary['pending_replenishment'] > 0 ? 'warning' : 'gray'),
        ];
    }
}
