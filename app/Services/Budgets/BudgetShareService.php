<?php

namespace App\Services\Budgets;

use App\Mail\BudgetSharedMail;
use App\Models\BudgetPlan;
use App\Models\User;
use App\Support\WhatsAppRedirect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class BudgetShareService
{
    public function __construct(
        private readonly BudgetPdfService $pdfService,
    ) {}

    public function preparePdfForShare(BudgetPlan $budgetPlan): BudgetPlan
    {
        $budgetPlan->refresh()->load('items');

        if ($budgetPlan->pdf_path === null || $budgetPlan->isDraft()) {
            return $this->pdfService->issue($budgetPlan);
        }

        return $this->pdfService->regenerate($budgetPlan);
    }

    public function ensureIssuedPdf(BudgetPlan $budgetPlan): BudgetPlan
    {
        return $this->preparePdfForShare($budgetPlan);
    }

    public function signedPdfUrl(BudgetPlan $budgetPlan): string
    {
        return URL::temporarySignedRoute(
            'budgets.pdf.signed',
            now()->addDays(7),
            ['budgetPlan' => $budgetPlan->id],
        );
    }

    public function buildSummaryMessage(BudgetPlan $budgetPlan, User $sender): string
    {
        $date = $budgetPlan->issued_at?->format('d/m/Y') ?? $budgetPlan->created_at->format('d/m/Y');

        return sprintf(
            'Presupuesto "%s" (%s) del %s. Generado en %s. Total asignado: B/. %s.',
            $budgetPlan->title,
            $budgetPlan->budget_number,
            $date,
            config('app.brand'),
            number_format((float) $budgetPlan->total_allocated, 2),
        );
    }

    public function sendEmail(BudgetPlan $budgetPlan, string $email, User $sender): void
    {
        $budgetPlan = $this->ensureIssuedPdf($budgetPlan);

        Mail::to($email)->send(new BudgetSharedMail($budgetPlan, $sender));
    }

    /**
     * @return array{web: string, app: string, pdf_url: string, filename: string, text: string}
     */
    public function whatsAppLinks(BudgetPlan $budgetPlan, string $phone, User $sender): array
    {
        $text = $this->buildWhatsAppMessage($budgetPlan, $sender);
        $links = WhatsAppRedirect::links($phone, $text);

        return [
            ...$links,
            'pdf_url' => $this->signedPdfUrl($budgetPlan),
            'filename' => "{$budgetPlan->budget_number}.pdf",
            'text' => $text,
        ];
    }

    public function whatsAppUrl(BudgetPlan $budgetPlan, string $phone, User $sender): string
    {
        return $this->whatsAppLinks($budgetPlan, $phone, $sender)['web'];
    }

    public function normalizePanamaPhone(string $phone): string
    {
        return WhatsAppRedirect::normalizePanamaPhone($phone);
    }

    public function buildWhatsAppMessage(BudgetPlan $budgetPlan, User $sender): string
    {
        $summary = $this->buildSummaryMessage($budgetPlan, $sender);
        $pdfUrl = $this->signedPdfUrl($budgetPlan);

        return "Hola,\n\n{$sender->name} te comparte un presupuesto desde ".config('app.brand').".\n\n{$summary}\n\nDescarga el PDF aquí:\n{$pdfUrl}\n\nAtentamente,\n{$sender->name}";
    }

    public function buildEmailBody(BudgetPlan $budgetPlan, User $sender): string
    {
        $summary = $this->buildSummaryMessage($budgetPlan, $sender);

        return "Hola,\n\nDesde ".config('app.brand')." se generó el siguiente presupuesto y te lo enviamos por este medio.\n\n{$summary}\n\nEl archivo PDF va adjunto a este correo.\n\nAtentamente,\n{$sender->name}";
    }
}
