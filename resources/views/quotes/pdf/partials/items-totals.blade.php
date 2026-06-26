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
                <td class="text-right">${{ number_format($item['unit_price'], 2) }}</td>
                <td class="text-right">{{ number_format($item['tax_rate'], 2) }}%</td>
                <td class="text-right">${{ number_format($item['tax_amount'], 2) }}</td>
                <td class="text-right">${{ number_format($item['line_total'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Subtotal:</td>
        <td class="text-right">${{ number_format($payload['totals']['subtotal'], 2) }}</td>
    </tr>
    <tr>
        <td>ITBMS:</td>
        <td class="text-right">${{ number_format($payload['totals']['tax_amount'], 2) }}</td>
    </tr>
    <tr class="total">
        <td>Total:</td>
        <td class="text-right">${{ number_format($payload['totals']['total'], 2) }}</td>
    </tr>
</table>
