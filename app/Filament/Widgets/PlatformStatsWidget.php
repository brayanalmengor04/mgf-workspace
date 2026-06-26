<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Panel de control';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Usuarios activos', User::query()->where('is_active', true)->count())
                ->description(User::query()->where('role', UserRole::Provider)->count().' proveedores')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->url(UserResource::getUrl('index')),
            Stat::make('Cotizaciones', Quote::query()->count())
                ->description(Quote::query()->where('created_at', '>=', now()->startOfMonth())->count().' este mes')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('success')
                ->url(QuoteResource::getUrl('index')),
            Stat::make('Plantillas', QuoteTemplate::query()->count())
                ->description('Configuraciones de PDF y emisor')
                ->descriptionIcon(Heroicon::OutlinedDocumentDuplicate)
                ->color('warning')
                ->url(QuoteTemplateResource::getUrl('index')),
        ];
    }
}
