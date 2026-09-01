<?php

namespace App\Filament\Pages;

use App\Services\Crm\CrmDashboardService;
use App\Support\AdminViewMode;
use App\Support\CrmNavigation;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.crm-dashboard';

    protected static ?string $navigationLabel = 'Escritorio';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return CrmNavigation::INICIO;
    }

    public string $trendMetric = 'available_balance';

    /** @var array<string, mixed> */
    public array $crm = [];

    public function mount(): void
    {
        $this->loadCrmData();
    }

    public function updatedTrendMetric(): void
    {
        $trend = $this->crm['trend'] ?? [];
        $data = match ($this->trendMetric) {
            'net_income' => $trend['net_income'] ?? [],
            'paid_amount' => $trend['paid_amount'] ?? [],
            default => $trend['available_balance'] ?? [],
        };

        $deltas = match ($this->trendMetric) {
            'net_income' => $this->crm['trend_point_deltas']['net_income'] ?? [],
            'paid_amount' => $this->crm['trend_point_deltas']['paid_amount'] ?? [],
            default => $this->crm['trend_point_deltas']['available_balance'] ?? [],
        };
        $labels = $trend['labels'] ?? [];

        $this->dispatch('crm-trend-changed', labels: $labels, data: $data, label: match ($this->trendMetric) {
            'net_income' => 'Ingreso neto',
            'paid_amount' => 'Pagado',
            default => 'Saldo disponible',
        }, deltas: $deltas, pointCount: max(count($labels), count($deltas) + 1));
    }

    public function getTitle(): string|Htmlable
    {
        if (AdminViewMode::isProviderPreview()) {
            return 'Escritorio (vista proveedor)';
        }

        return 'CRM presupuestal';
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function getVisibleWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleViewMode')
                ->label(fn (): string => AdminViewMode::isProviderPreview()
                    ? 'Volver a vista administrador'
                    : 'Ver como proveedor')
                ->icon(fn () => AdminViewMode::isProviderPreview()
                    ? Heroicon::OutlinedShieldCheck
                    : Heroicon::OutlinedEye)
                ->color(fn (): string => AdminViewMode::isProviderPreview() ? 'warning' : 'gray')
                ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                ->action(function (): void {
                    AdminViewMode::toggle();

                    $this->redirect(static::getUrl(), navigate: true);
                }),
        ];
    }

    protected function loadCrmData(): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->crm = [];

            return;
        }

        $this->crm = app(CrmDashboardService::class)->forUser($user);
    }
}
