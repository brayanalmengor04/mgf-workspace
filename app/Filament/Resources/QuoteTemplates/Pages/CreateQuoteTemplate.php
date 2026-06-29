<?php

namespace App\Filament\Resources\QuoteTemplates\Pages;

use App\Filament\Concerns\InteractsWithEmbeddedWizard;
use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuoteTemplate extends CreateRecord
{
    use InteractsWithEmbeddedWizard;

    protected static string $resource = QuoteTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
