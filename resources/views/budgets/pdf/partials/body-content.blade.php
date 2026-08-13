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
                    <td>
                        <span class="concept">{{ $item['concept'] }}</span>
                        @if(!empty($item['is_paid']))
                            <div style="margin-top: 2px;">
                                <span style="font-size: 8px; font-weight: bold; color: #16a34a; border: 1px solid #16a34a; padding: 1px 4px; border-radius: 3px;">PAGADO</span>
                                @if(!empty($item['paid_at']))
                                    <span style="font-size: 8px; color: #64748b; margin-left: 4px;">{{ \Carbon\Carbon::parse($item['paid_at'])->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
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

@if(!empty($payload['is_paid']))
    <div style="text-align: center; margin: 20px 0;">
        <span style="display: inline-block; padding: 10px 20px; font-size: 20px; font-weight: bold; color: #16a34a; border: 3px solid #16a34a; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.1em; transform: rotate(-5deg);">
            PAGADO
        </span>
    </div>
@endif

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

@include('pdf.partials.branding')
