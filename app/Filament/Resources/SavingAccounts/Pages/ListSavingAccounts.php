<?php

namespace App\Filament\Resources\SavingAccounts\Pages;

use App\Filament\Resources\SavingAccounts\SavingAccountResource;
use App\Filament\Widgets\SavingsActivityTrendChartWidget;
use App\Filament\Widgets\SavingsBreakdownChartWidget;
use App\Filament\Widgets\SavingsGapChartWidget;
use App\Filament\Widgets\SavingsPanelStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingAccounts extends ListRecords
{
    protected static string $resource = SavingAccountResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            SavingsPanelStatsWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            SavingsBreakdownChartWidget::class,
            SavingsGapChartWidget::class,
            SavingsActivityTrendChartWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva cuenta'),
        ];
    }
}
