<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 20px 24px; line-height: 1.35; }
        .title { font-size: 16px; font-weight: bold; color: {{ $primaryColor }}; margin: 0; }
        .meta { font-size: 8px; color: #666; margin: 4px 0 12px; }
        .income-row { margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid {{ $primaryColor }}; }
        .income-label { font-size: 8px; text-transform: uppercase; color: {{ $primaryColor }}; }
        .income-amount { font-size: 20px; font-weight: bold; }
        .section-title { font-size: 8px; text-transform: uppercase; color: #333; margin: 0; font-weight: bold; }
        .section-heading td { padding-top: 8px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: {{ $primaryColor }}; color: #fff; font-size: 7px; padding: 4px 5px; text-align: left; }
        table.items td { padding: 5px; border-bottom: 1px solid #ddd; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 72px; }
        .concept { font-weight: 600; }
        .notes { color: #666; font-size: 8px; }
        .section-subtotal td { font-weight: bold; border-top: 1px solid #999; }
        .summary { margin-top: 12px; font-size: 9px; }
        .summary-table { width: 100%; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .footer { margin-top: 12px; font-size: 8px; color: #666; }
        .doc-id { font-size: 7px; color: #999; text-align: right; margin-top: 8px; }
    </style>
</head>
<body>
    <h1 class="title">{{ $payload['title'] }}</h1>
    <div class="meta">{{ $payload['period_label'] }} · {{ $payload['budget_number'] }} · {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
    <div class="income-row">
        <div class="income-label">Salario neto</div>
        <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
    </div>
    @include('budgets.pdf.partials.body-content')
</body>
</html>
