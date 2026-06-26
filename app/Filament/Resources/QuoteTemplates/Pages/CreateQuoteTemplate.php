<?php

namespace App\Filament\Resources\QuoteTemplates\Pages;

use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuoteTemplate extends CreateRecord
{
    protected static string $resource = QuoteTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
