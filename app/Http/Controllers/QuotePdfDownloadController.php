<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\Quotes\QuotePdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuotePdfDownloadController extends Controller
{
    public function __invoke(Request $request, Quote $quote, QuotePdfService $pdfService): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $path = $pdfService->downloadPath($quote);

        if ($path === null) {
            abort(404);
        }

        return response()->download($path, "{$quote->quote_number}.pdf");
    }
}
