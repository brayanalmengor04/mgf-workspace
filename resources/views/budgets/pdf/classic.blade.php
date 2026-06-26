<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }} — {{ $payload['budget_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 32px 36px;
            line-height: 1.45;
        }
        .header { margin-bottom: 28px; }
        .title {
            font-size: 26px;
            font-weight: bold;
            color: #111;
            margin: 0 0 6px;
            letter-spacing: -0.02em;
        }
        .subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .meta {
            margin-top: 10px;
            font-size: 10px;
            color: #94a3b8;
        }
        .income-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 18px 20px;
            margin-bottom: 28px;
            background: #fff;
        }
        .income-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 8px;
        }
        .income-amount {
            font-size: 32px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .income-notes {
            font-size: 11px;
            color: #64748b;
            margin-top: 8px;
        }
        .section { margin-bottom: 24px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            margin: 0 0 10px;
            letter-spacing: 0.04em;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items th {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: normal;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        table.items th.col-amount,
        table.items th.col-pct,
        table.items td.col-amount,
        table.items td.col-pct {
            text-align: right;
            width: 90px;
        }
        .concept { font-weight: 600; color: #0f172a; }
        .notes { color: #64748b; font-size: 10px; }
        .amount { font-weight: bold; color: #0f172a; }
        .pct { color: #64748b; }
        .section-subtotal td {
            border-top: 1px solid #cbd5e1;
            border-bottom: none;
            font-weight: bold;
            padding-top: 8px;
        }
        .summary {
            margin-top: 32px;
            border-top: 2px solid #0f172a;
            padding-top: 16px;
        }
        .summary-table { width: 100%; }
        .summary-table td { padding: 5px 0; }
        .summary-table .label { color: #64748b; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .remaining .value { font-size: 16px; }
        .summary-table .remaining.positive .value { color: #059669; }
        .summary-table .remaining.negative .value { color: #dc2626; }
        .footer {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #64748b;
        }
        .doc-id {
            margin-top: 20px;
            font-size: 9px;
            color: #94a3b8;
            text-align: right;
        }
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
        <div class="income-amount">
            {{ $payload['currency_symbol'] }}{{ number_format($payload['net_income'], 2) }}
        </div>
        @if(filled($payload['income_notes'] ?? null))
            <div class="income-notes">{{ $payload['income_notes'] }}</div>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Notas</th>
                <th class="col-amount">Monto</th>
                <th class="col-pct">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['sections'] as $section)
                <tr>
                    <td colspan="4" style="padding-top:16px;padding-bottom:4px;border-bottom:none;">
                        <div class="section-title">{{ $section['letter'] }}. {{ strtoupper($section['label']) }}</div>
                    </td>
                </tr>
                @foreach($section['items'] as $item)
                    <tr>
                        <td><span class="concept">{{ $item['concept'] }}</span></td>
                        <td><span class="notes">{{ $item['notes'] ?? '—' }}</span></td>
                        <td class="col-amount amount">-{{ $payload['currency_symbol'] }}{{ number_format($item['amount'], 2) }}</td>
                        <td class="col-pct pct">{{ number_format($item['percentage'], 1) }}%</td>
                    </tr>
                @endforeach
                @if(count($section['items']) > 1)
                    <tr class="section-subtotal">
                        <td colspan="2">Subtotal {{ $section['label'] }}</td>
                        <td class="col-amount amount">-{{ $payload['currency_symbol'] }}{{ number_format($section['subtotal'], 2) }}</td>
                        <td class="col-pct pct">{{ number_format($section['percentage'], 1) }}%</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="label">Total asignado</td>
                <td class="value">-{{ $payload['currency_symbol'] }}{{ number_format($payload['totals']['total_allocated'], 2) }} ({{ number_format($payload['totals']['allocation_rate'], 1) }}%)</td>
            </tr>
            <tr class="remaining {{ $payload['totals']['remaining_balance'] >= 0 ? 'positive' : 'negative' }}">
                <td class="label">{{ $payload['totals']['remaining_balance'] >= 0 ? 'Disponible libre' : 'Excedido del presupuesto' }}</td>
                <td class="value">{{ $payload['currency_symbol'] }}{{ number_format(abs($payload['totals']['remaining_balance']), 2) }}</td>
            </tr>
        </table>
    </div>

    @if(filled($payload['footer_notes'] ?? null))
        <div class="footer">{{ $payload['footer_notes'] }}</div>
    @endif

    <div class="doc-id">{{ $payload['budget_number'] }}</div>
</body>
</html>
