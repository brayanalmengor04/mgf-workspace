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
                <td colspan="4" class="section-heading">
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
