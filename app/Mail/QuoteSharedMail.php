<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\User;
use App\Services\Quotes\QuotePdfService;
use App\Services\Quotes\QuoteShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public User $sender,
    ) {}

    public function envelope(): Envelope
    {
        $brand = (string) config('app.brand');

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name', $brand),
            ),
            subject: "Cotización {$this->quote->quote_number} — {$brand}",
        );
    }

    public function content(): Content
    {
        $shareService = app(QuoteShareService::class);

        return new Content(
            text: 'mail.quote-shared-text',
            with: [
                'body' => $shareService->buildEmailBody($this->quote, $this->sender),
                'appBrand' => config('app.brand'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $path = app(QuotePdfService::class)->downloadPath($this->quote);

        if ($path === null) {
            return [];
        }

        return [
            Attachment::fromPath($path)
                ->as("{$this->quote->quote_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
