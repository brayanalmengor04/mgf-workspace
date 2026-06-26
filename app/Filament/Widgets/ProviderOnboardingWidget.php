<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProviderOnboardingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Tu espacio de trabajo';

    protected ?string $description = 'Sigue estos pasos para emitir tu primera cotización.';

    public static function canView(): bool
    {
        return auth()->user()?->isProvider() ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $templates = QuoteTemplate::query()->forUser($user)->count();
        $quotes = Quote::query()->forUser($user)->count();
        $issued = Quote::query()->forUser($user)->where('status', QuoteStatus::Issued)->count();

        return [
            Stat::make('1 · Plantilla', $templates > 0 ? 'Lista' : 'Pendiente')
                ->description($templates > 0 ? 'Tu plantilla está configurada' : 'Configura tu logo, emisor y banco')
                ->descriptionIcon($templates > 0 ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedDocumentDuplicate)
                ->color($templates > 0 ? 'success' : 'warning')
                ->url(QuoteTemplateResource::getUrl($templates > 0 ? 'index' : 'create')),
            Stat::make('2 · Cotización', $quotes > 0 ? "{$quotes} creada(s)" : 'Pendiente')
                ->description($quotes > 0 ? 'Agrega items y emite el PDF' : 'Crea tu primera cotización')
                ->descriptionIcon($quotes > 0 ? Heroicon::OutlinedDocumentText : Heroicon::OutlinedPlusCircle)
                ->color($quotes > 0 ? 'success' : 'gray')
                ->url(QuoteResource::getUrl($quotes > 0 ? 'index' : 'create')),
            Stat::make('3 · Emitidas', $issued)
                ->description('Cotizaciones con PDF generado')
                ->descriptionIcon(Heroicon::OutlinedPaperAirplane)
                ->color($issued > 0 ? 'primary' : 'gray')
                ->url(QuoteResource::getUrl('index')),
        ];
    }
}
