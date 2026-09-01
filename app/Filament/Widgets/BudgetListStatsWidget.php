<?php

namespace App\Filament\Widgets;

use App\Services\Crm\CrmDashboardService;
use Filament\Widgets\Widget;

class BudgetListStatsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.budget-list-stats';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return ['stats' => []];
        }

        return [
            'stats' => app(CrmDashboardService::class)->budgetListStatsFor($user),
        ];
    }
}
