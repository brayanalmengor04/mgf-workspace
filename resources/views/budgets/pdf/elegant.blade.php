<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2c2c2c; margin: 0; padding: 24px; }
        .frame { border: 2px solid {{ $primaryColor }}; padding: 18px 20px; }
        .inner { border: 1px solid #d4d4d4; padding: 16px 18px; }
        .header { text-align: center; border-bottom: 1px double {{ $primaryColor }}; padding-bottom: 12px; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: normal; letter-spacing: 3px; text-transform: uppercase; color: {{ $primaryColor }}; margin: 0; }
        .subtitle { font-size: 11px; color: #666; margin: 6px 0 0; }
        .meta { font-size: 10px; color: #888; margin-top: 6px; }
        .income-card { text-align: center; margin-bottom: 20px; padding: 12px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        .income-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: {{ $primaryColor }}; }
        .income-amount { font-size: 28px; font-weight: bold; margin-top: 6px; }
        .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: {{ $primaryColor }}; margin: 0; }
        .section-heading td { padding-top: 14px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 6px 5px; font-size: 8px; text-transform: uppercase; }
        table.items td { border-bottom: 1px solid #eee; padding: 7px 5px; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 85px; }
        .concept { font-weight: 600; }
        .notes { color: #666; font-size: 9px; }
        .section-subtotal td { border-top: 1px solid #ccc; font-weight: bold; }
        .summary { margin-top: 18px; border-top: 1px double #333; padding-top: 10px; }
        .summary-table { width: 100%; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #666; }
        .doc-id { margin-top: 12px; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="inner">
            <div class="header">
                <h1 class="title">{{ $payload['title'] }}</h1>
                @if(filled($payload['subtitle'] ?? null))<p class="subtitle">{{ $payload['subtitle'] }}</p>@endif
                <div class="meta">{{ $payload['period_label'] }} · {{ $payload['budget_number'] }}</div>
            </div>
            <div class="income-card">
                <div class="income-label">Salario neto (recibido)</div>
                <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
                @if(filled($payload['income_notes'] ?? null))<div style="font-size:10px;color:#666;margin-top:6px;">{{ $payload['income_notes'] }}</div>@endif
            </div>
            @include('budgets.pdf.partials.body-content')
        </div>
    </div>
</body>
</html>
