<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 0; line-height: 1.45; }
        .banner { background: {{ $primaryColor }}; color: #fff; padding: 22px 32px; margin-bottom: 24px; }
        .banner .title { font-size: 24px; font-weight: bold; margin: 0 0 4px; color: #fff; }
        .banner .subtitle { font-size: 12px; margin: 0; opacity: 0.9; }
        .banner .meta { margin-top: 8px; font-size: 10px; opacity: 0.85; }
        .content { padding: 0 32px 32px; }
        .income-card { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid {{ $primaryColor }}; padding: 16px 18px; margin-bottom: 24px; }
        .income-label { font-size: 10px; text-transform: uppercase; color: {{ $primaryColor }}; margin-bottom: 6px; font-weight: bold; }
        .income-amount { font-size: 28px; font-weight: bold; color: #0f172a; }
        .income-notes { font-size: 10px; color: #64748b; margin-top: 6px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: {{ $primaryColor }}; margin: 0; }
        .section-heading td { padding-top: 14px; padding-bottom: 4px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: {{ $primaryColor }}; color: #fff; font-size: 9px; text-transform: uppercase; padding: 7px 8px; text-align: left; }
        table.items td { padding: 9px 8px; border-bottom: 1px solid #eee; }
        table.items tbody tr:nth-child(even) { background: #fafafa; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 90px; }
        .concept { font-weight: 600; }
        .notes { color: #64748b; font-size: 10px; }
        .amount { font-weight: bold; }
        .pct { color: #64748b; }
        .section-subtotal td { border-top: 1px solid {{ $primaryColor }}; font-weight: bold; }
        .summary { margin-top: 24px; border-top: 2px solid {{ $primaryColor }}; padding-top: 14px; }
        .summary-table { width: 100%; }
        .summary-table td { padding: 5px 0; }
        .summary-table .label { color: #64748b; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .remaining.positive .value { color: #059669; font-size: 15px; }
        .summary-table .remaining.negative .value { color: #dc2626; font-size: 15px; }
        .footer { margin-top: 24px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .doc-id { margin-top: 16px; font-size: 9px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="banner">
        <h1 class="title">{{ $payload['title'] }}</h1>
        @if(filled($payload['subtitle'] ?? null))
            <p class="subtitle">{{ $payload['subtitle'] }}</p>
        @endif
        <div class="meta">{{ $payload['period_label'] }} · {{ $payload['budget_number'] }} · {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
    </div>
    <div class="content">
        <div class="income-card">
            <div class="income-label">Salario neto (recibido)</div>
            <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
            @if(filled($payload['income_notes'] ?? null))
                <div class="income-notes">{{ $payload['income_notes'] }}</div>
            @endif
        </div>
        @include('budgets.pdf.partials.body-content')
    </div>
</body>
</html>
