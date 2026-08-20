<?php

namespace App\Filament\Pages;

use App\Support\AdminViewMode;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Escritorio';

    public function getTitle(): string|Htmlable
    {
        if (AdminViewMode::isProviderPreview()) {
            return 'Escritorio (vista proveedor)';
        }

        return 'Escritorio';
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'xl' => 4,
        ];
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
}
