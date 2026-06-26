<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Concerns\RecalculatesQuoteTotals;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\QuoteTemplate;
use App\Services\Quotes\QuoteNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    use RecalculatesQuoteTotals;

    protected static string $resource = QuoteResource::class;

    public function mount(): void
    {
        parent::mount();

        $templateId = request()->query('template_id');

        if ($templateId === null) {
            $default = QuoteTemplate::query()->where('is_default', true)->first();

            if ($default !== null) {
                $templateId = (string) $default->id;
            }
        }

        if ($templateId === null) {
            return;
        }

        $template = QuoteTemplate::find($templateId);

        if ($template === null) {
            return;
        }

        $this->form->fill([
            'quote_template_id' => $template->id,
            'issuer_name' => $template->issuer_name,
            'issuer_ruc' => $template->issuer_ruc,
            'issuer_dv' => $template->issuer_dv,
            'issuer_has_dv' => $template->issuer_has_dv,
            'issuer_address' => $template->issuer_address,
            'issuer_phone' => $template->issuer_phone,
            'issuer_email' => $template->issuer_email,
            'bank_name' => $template->bank_name,
            'bank_account_number' => $template->bank_account_number,
            'yappy_id' => $template->yappy_id,
            'footer_notes' => $template->footer_notes,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['quote_number'] = app(QuoteNumberGenerator::class)->generate();
        $data['status'] = QuoteStatus::Draft->value;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->recalculateQuoteTotals($this->record);
    }
}
