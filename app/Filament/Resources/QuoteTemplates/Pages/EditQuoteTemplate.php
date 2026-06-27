<?php

namespace App\Filament\Resources\QuoteTemplates\Pages;

use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditQuoteTemplate extends EditRecord
{
    protected static string $resource = QuoteTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Vista previa PDF')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (): string => QuoteTemplateResource::getUrl('preview', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }
}
