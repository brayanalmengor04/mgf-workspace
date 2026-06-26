@include('quotes.pdf.classic', [
    'quote' => $quote,
    'payload' => $payload,
    'primaryColor' => $primaryColor ?? '#d97706',
    'logoDataUri' => $logoDataUri ?? null,
])
