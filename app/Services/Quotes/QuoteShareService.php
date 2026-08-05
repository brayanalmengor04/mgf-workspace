<?php

namespace App\Services\Quotes;

use App\Mail\QuoteSharedMail;
use App\Models\Quote;
use App\Models\User;
use App\Support\WhatsAppRedirect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class QuoteShareService
{
    public function __construct(
        private readonly QuotePdfService $pdfService,
    ) {}

    public function preparePdfForShare(Quote $quote): Quote
    {
        $quote->refresh()->load(['items', 'template']);

        if ($quote->pdf_path === null || $quote->isDraft()) {
            return $this->pdfService->issue($quote);
        }

        return $this->pdfService->regenerate($quote);
    }

    public function ensureIssuedPdf(Quote $quote): Quote
    {
        return $this->preparePdfForShare($quote);
    }

    public function signedPdfUrl(Quote $quote): string
    {
        return URL::temporarySignedRoute(
            'quotes.pdf.signed',
            now()->addDays(7),
            ['quote' => $quote->id],
        );
    }

    public function buildSummaryMessage(Quote $quote, User $sender): string
    {
        $date = $quote->issued_at?->format('d/m/Y') ?? $quote->quote_date?->format('d/m/Y') ?? $quote->created_at->format('d/m/Y');

        return sprintf(
            'Cotización para %s (%s) del %s. Generada en %s. Total: B/. %s.',
            $quote->recipient_name,
            $quote->quote_number,
            $date,
            config('app.brand'),
            number_format((float) $quote->total, 2),
        );
    }

    public function sendEmail(Quote $quote, string $email, User $sender): void
    {
        $quote = $this->ensureIssuedPdf($quote);

        Mail::to($email)->send(new QuoteSharedMail($quote, $sender));
    }

    /**
     * @return array{web: string, app: string, pdf_url: string, filename: string, text: string}
     */
    public function whatsAppLinks(Quote $quote, string $phone, User $sender): array
    {
        $text = $this->buildWhatsAppMessage($quote, $sender);
        $links = WhatsAppRedirect::links($phone, $text);

        return [
            ...$links,
            'pdf_url' => $this->signedPdfUrl($quote),
            'filename' => "{$quote->quote_number}.pdf",
            'text' => $text,
        ];
    }

    public function whatsAppUrl(Quote $quote, string $phone, User $sender): string
    {
        return $this->whatsAppLinks($quote, $phone, $sender)['web'];
    }

    public function normalizePanamaPhone(string $phone): string
    {
        return WhatsAppRedirect::normalizePanamaPhone($phone);
    }

    public function buildWhatsAppMessage(Quote $quote, User $sender): string
    {
        $summary = $this->buildSummaryMessage($quote, $sender);
        $pdfUrl = $this->signedPdfUrl($quote);

        return "Hola,\n\n{$sender->name} te comparte una cotización desde ".config('app.brand').".\n\n{$summary}\n\nDescarga el PDF aquí:\n{$pdfUrl}\n\nAtentamente,\n{$sender->name}";
    }

    public function buildEmailBody(Quote $quote, User $sender): string
    {
        $summary = $this->buildSummaryMessage($quote, $sender);

        return "Hola,\n\nDesde ".config('app.brand')." se generó la siguiente cotización y te la enviamos por este medio.\n\n{$summary}\n\nEl archivo PDF va adjunto a este correo.\n\nAtentamente,\n{$sender->name}";
    }
}
