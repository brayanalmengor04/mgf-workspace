<?php

namespace App\Mail;

use App\Models\BudgetPlan;
use App\Models\User;
use App\Services\Budgets\BudgetPdfService;
use App\Services\Budgets\BudgetShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BudgetSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BudgetPlan $budgetPlan,
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
            subject: "Presupuesto {$this->budgetPlan->budget_number} — {$brand}",
        );
    }

    public function content(): Content
    {
        $shareService = app(BudgetShareService::class);

        return new Content(
            text: 'mail.budget-shared-text',
            with: [
                'body' => $shareService->buildEmailBody($this->budgetPlan, $this->sender),
                'appBrand' => config('app.brand'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $path = app(BudgetPdfService::class)->downloadPath($this->budgetPlan);

        if ($path === null) {
            return [];
        }

        return [
            Attachment::fromPath($path)
                ->as("{$this->budgetPlan->budget_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
