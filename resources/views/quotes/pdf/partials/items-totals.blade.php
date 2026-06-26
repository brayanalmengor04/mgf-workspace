@php
    use App\Support\MoneyFormatter;

    $currency = $payload['currency'] ?? 'PAB';
@endphp

<table class="items">
    <thead>
        <tr>
            <th style="width: 8%">Cant.</th>
            <th>Descripción</th>
            <th style="width: 14%" class="text-right">P. Unit.</th>
            <th style="width: 10%" class="text-right">ITBMS %</th>
            <th style="width: 14%" class="text-right">ITBMS</th>
            <th style="width: 14%" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payload['items'] as $item)
            <tr>
                <td>{{ number_format($item['quantity'], 2) }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">{{ MoneyFormatter::format($item['unit_price'], $currency) }}</td>
                <td class="text-right">{{ number_format($item['tax_rate'], 2) }}%</td>
                <td class="text-right">{{ MoneyFormatter::format($item['tax_amount'], $currency) }}</td>
                <td class="text-right">{{ MoneyFormatter::format($item['line_total'], $currency) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Subtotal:</td>
        <td class="text-right">{{ MoneyFormatter::format($payload['totals']['subtotal'], $currency) }}</td>
    </tr>
    <tr>
        <td>ITBMS:</td>
        <td class="text-right">{{ MoneyFormatter::format($payload['totals']['tax_amount'], $currency) }}</td>
    </tr>
    <tr class="total">
        <td>Total ({{ $payload['currency_label'] ?? $currency }}):</td>
        <td class="text-right">{{ MoneyFormatter::format($payload['totals']['total'], $currency) }}</td>
    </tr>
</table>
