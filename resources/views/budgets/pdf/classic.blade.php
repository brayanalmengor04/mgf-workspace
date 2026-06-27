<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 32px 36px; line-height: 1.45; }
        .header { margin-bottom: 28px; border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 12px; }
        .title { font-size: 26px; font-weight: bold; color: {{ $primaryColor }}; margin: 0 0 6px; }
        .subtitle { font-size: 13px; color: #64748b; margin: 0; }
        .meta { margin-top: 10px; font-size: 10px; color: #94a3b8; }
        .income-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 18px 20px; margin-bottom: 28px; background: #fff; border-left: 4px solid {{ $primaryColor }}; }
        .income-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 8px; }
        .income-amount { font-size: 32px; font-weight: bold; color: #0f172a; margin: 0; }
        .income-notes { font-size: 11px; color: #64748b; margin-top: 8px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: {{ $primaryColor }}; margin: 0; letter-spacing: 0.04em; }
        .section-heading td { padding-top: 16px; padding-bottom: 4px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: normal; text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        table.items td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 90px; }
        .concept { font-weight: 600; color: #0f172a; }
        .notes { color: #64748b; font-size: 10px; }
        .amount { font-weight: bold; color: #0f172a; }
        .pct { color: #64748b; }
        .section-subtotal td { border-top: 1px solid #cbd5e1; border-bottom: none; font-weight: bold; padding-top: 8px; }
        .summary { margin-top: 32px; border-top: 2px solid {{ $primaryColor }}; padding-top: 16px; }
        .summary-table { width: 100%; }
        .summary-table td { padding: 5px 0; }
        .summary-table .label { color: #64748b; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .remaining .value { font-size: 16px; }
        .summary-table .remaining.positive .value { color: #059669; }
        .summary-table .remaining.negative .value { color: #dc2626; }
        .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; }
        .doc-id { margin-top: 20px; font-size: 9px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $payload['title'] }}</h1>
        @if(filled($payload['subtitle'] ?? null))
            <p class="subtitle">{{ $payload['subtitle'] }}</p>
        @endif
        <div class="meta">
            {{ $payload['period_label'] }} · {{ $payload['budget_number'] }} · {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}
        </div>
    </div>

    <div class="income-card">
        <div class="income-label">Salario neto (recibido)</div>
        <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
        @if(filled($payload['income_notes'] ?? null))
            <div class="income-notes">{{ $payload['income_notes'] }}</div>
        @endif
    </div>

    @include('budgets.pdf.partials.body-content')
</body>
</html>
