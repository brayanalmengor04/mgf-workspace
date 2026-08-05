<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BudgetPdfDownloadController extends Controller
{
    public function __invoke(Request $request, BudgetPlan $budgetPlan, BudgetPdfService $pdfService): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $path = $pdfService->downloadPath($budgetPlan);

        if ($path === null) {
            abort(404);
        }

        return response()->download($path, "{$budgetPlan->budget_number}.pdf");
    }
}
