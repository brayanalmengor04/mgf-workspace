<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .sidebar { width: 6px; background: {{ $primaryColor }}; }
        .content { padding: 28px 28px 28px 20px; vertical-align: top; }
        .title { font-size: 22px; font-weight: bold; color: {{ $primaryColor }}; margin: 0 0 4px; }
        .subtitle { font-size: 12px; color: #64748b; margin: 0; }
        .meta { font-size: 10px; color: #94a3b8; margin: 8px 0 20px; }
        .income-card { background: #f9fafb; border: 1px solid #e5e7eb; padding: 14px 16px; margin-bottom: 22px; }
        .income-label { font-size: 9px; text-transform: uppercase; color: {{ $primaryColor }}; font-weight: bold; margin-bottom: 6px; }
        .income-amount { font-size: 26px; font-weight: bold; }
        .section-title { font-size: 10px; text-transform: uppercase; color: {{ $primaryColor }}; margin: 0; font-weight: bold; }
        .section-heading td { padding-top: 14px; border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; padding: 7px 8px; border: 1px solid #e5e7eb; }
        table.items td { padding: 8px; border: 1px solid #e5e7eb; }
        table.items th.col-amount, table.items th.col-pct, table.items td.col-amount, table.items td.col-pct { text-align: right; width: 88px; }
        .concept { font-weight: 600; }
        .notes { color: #64748b; font-size: 10px; }
        .section-subtotal td { background: #f9fafb; font-weight: bold; }
        .summary { margin-top: 22px; border: 1px solid #e5e7eb; padding: 12px 14px; background: #f9fafb; }
        .summary-table { width: 100%; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .remaining.positive .value { color: #059669; }
        .summary-table .remaining.negative .value { color: #dc2626; }
        .footer { margin-top: 20px; font-size: 10px; color: #64748b; }
        .doc-id { margin-top: 14px; font-size: 9px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <table class="layout">
        <tr>
            <td class="sidebar">&nbsp;</td>
            <td class="content">
                <h1 class="title">{{ $payload['title'] }}</h1>
                @if(filled($payload['subtitle'] ?? null))<p class="subtitle">{{ $payload['subtitle'] }}</p>@endif
                <div class="meta">{{ $payload['period_label'] }} · {{ $payload['budget_number'] }} · {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                <div class="income-card">
                    <div class="income-label">Salario neto (recibido)</div>
                    <div class="income-amount">{{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}</div>
                    @if(filled($payload['income_notes'] ?? null))<div style="font-size:10px;color:#64748b;margin-top:6px;">{{ $payload['income_notes'] }}</div>@endif
                </div>
                @include('budgets.pdf.partials.body-content')
            </td>
        </tr>
    </table>
</body>
</html>
