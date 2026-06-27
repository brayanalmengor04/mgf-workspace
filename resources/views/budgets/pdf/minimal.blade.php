<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; padding: 28px 32px; line-height: 1.4; }
        .header { border-bottom: 1px solid #222; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; margin: 0; }
        .subtitle { font-size: 11px; color: #555; margin: 4px 0 0; }
        .meta { font-size: 9px; color: #777; margin-top: 6px; }
        .income-card { margin-bottom: 22px; }
        .income-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        .income-amount { font-size: 24px; font-weight: bold; margin-top: 4px; }
        .income-notes { font-size: 9px; color: #666; margin-top: 4px; }
        .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #333; margin: 0; }
        .section-heading td { padding-top: 12px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { border-bottom: 1px solid #222; padding: 5px 4px; font-size: 8px; text-transform: uppercase; text-align: left; }
        table.items td { border-bottom: 1px solid #eee; padding: 7px 4px; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 80px; }
        .concept { font-weight: 600; }
        .notes { color: #666; font-size: 9px; }
        .section-subtotal td { border-top: 1px solid #222; font-weight: bold; }
        .summary { margin-top: 20px; border-top: 1px solid #222; padding-top: 10px; }
        .summary-table { width: 100%; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .remaining.positive .value { color: #059669; }
        .summary-table .remaining.negative .value { color: #dc2626; }
        .footer { margin-top: 20px; font-size: 9px; color: #666; }
        .doc-id { margin-top: 14px; font-size: 8px; color: #999; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $payload['title'] }}</h1>
        @if(filled($payload['subtitle'] ?? null))<p class="subtitle">{{ $payload['subtitle'] }}</p>@endif
        <div class="meta">{{ $payload['period_label'] }} · {{ $payload['budget_number'] }} · {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
    </div>
    <div class="income-card">
        <div class="income-label">Salario neto (recibido)</div>
        <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
        @if(filled($payload['income_notes'] ?? null))<div class="income-notes">{{ $payload['income_notes'] }}</div>@endif
    </div>
    @include('budgets.pdf.partials.body-content')
</body>
</html>
