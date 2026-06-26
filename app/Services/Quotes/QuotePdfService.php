<?php

namespace App\Services\Quotes;

use App\Enums\QuotePdfLayout;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotePdfService
{
    public function __construct(
        private readonly QuoteCalculator $calculator,
    ) {}

    public function issue(Quote $quote): Quote
    {
        $quote->load(['items', 'template']);

        $payload = $this->buildPayload($quote);
        $pdfPath = $this->renderPdf($quote, $payload);

        $quote->forceFill([
            'status' => QuoteStatus::Issued,
            'generated_payload' => $payload,
            'pdf_path' => $pdfPath,
            'issued_at' => now(),
        ])->save();

        activity()
            ->performedOn($quote)
            ->causedBy(auth()->user())
            ->event('issued')
            ->withProperties(['quote_number' => $quote->quote_number])
            ->log('Cotización emitida');

        return $quote->refresh();
    }

    public function regenerate(Quote $quote): Quote
    {
        $quote->load(['items', 'template']);

        $payload = $quote->generated_payload ?? $this->buildPayload($quote);
        $pdfPath = $this->renderPdf($quote, $payload);

        $quote->forceFill([
            'generated_payload' => $payload,
            'pdf_path' => $pdfPath,
        ])->save();

        activity()
            ->performedOn($quote)
            ->causedBy(auth()->user())
            ->event('regenerated')
            ->withProperties(['quote_number' => $quote->quote_number])
            ->log('PDF de cotización regenerado');

        return $quote->refresh();
    }

    public function downloadPath(Quote $quote): ?string
    {
        if ($quote->pdf_path === null || ! Storage::disk('local')->exists($quote->pdf_path)) {
            return null;
        }

        return Storage::disk('local')->path($quote->pdf_path);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Quote $quote): array
    {
        $items = $quote->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'tax_amount' => (float) $item->tax_amount,
            'line_total' => (float) $item->line_total,
        ])->values()->all();

        return [
            'quote_number' => $quote->quote_number,
            'issued_at' => ($quote->issued_at ?? now())->toIso8601String(),
            'issuer' => [
                'name' => $quote->issuer_name,
                'ruc' => $quote->issuer_ruc,
                'dv' => $quote->issuer_dv,
                'has_dv' => $quote->issuer_has_dv,
                'address' => $quote->issuer_address,
                'phone' => $quote->issuer_phone,
                'email' => $quote->issuer_email,
            ],
            'recipient' => [
                'name' => $quote->recipient_name,
                'ruc' => $quote->recipient_ruc,
                'dv' => $quote->recipient_dv,
                'has_dv' => $quote->recipient_has_dv,
                'address' => $quote->recipient_address,
                'phone' => $quote->recipient_phone,
                'email' => $quote->recipient_email,
            ],
            'items' => $items,
            'totals' => [
                'subtotal' => (float) $quote->subtotal,
                'tax_amount' => (float) $quote->tax_amount,
                'total' => (float) $quote->total,
            ],
            'footer' => [
                'bank_name' => $quote->bank_name,
                'bank_account_number' => $quote->bank_account_number,
                'yappy_id' => $quote->yappy_id,
                'notes' => $quote->footer_notes,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderPdf(Quote $quote, array $payload): string
    {
        $filename = 'quotes/'.Str::slug($quote->quote_number).'-'.Str::uuid().'.pdf';

        $template = $quote->template;
        $layout = QuotePdfLayout::tryFrom((string) ($template?->pdf_layout ?? '')) ?? QuotePdfLayout::Classic;

        $pdf = Pdf::loadView($layout->view(), [
            'quote' => $quote,
            'payload' => $payload,
            'primaryColor' => $template?->primary_color ?? '#d97706',
            'logoDataUri' => $this->resolveLogoDataUri($template?->logo_path),
        ])->setPaper('letter');

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    private function resolveLogoDataUri(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($logoPath);
        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }
}
