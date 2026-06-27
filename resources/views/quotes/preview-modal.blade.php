@php
    use App\Support\MoneyFormatter;
    
    $currency = $data['currency'] ?? 'PAB';
@endphp

<div class="p-6 bg-white dark:bg-gray-900 rounded-lg text-sm text-gray-800 dark:text-gray-200 border dark:border-gray-700 shadow-sm max-w-4xl mx-auto font-sans">
    <!-- Header -->
    <div class="flex justify-between items-start border-b-2 border-primary-600 pb-4 mb-6">
        <div>
            @if(!empty($data['logoDataUri']))
                <img src="{{ $data['logoDataUri'] }}" class="max-h-16" alt="Logo">
            @endif
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-primary-600">COTIZACIÓN</div>
            <div class="font-bold text-lg">{{ $data['quote_number'] ?: 'Borrador' }}</div>
            <div class="text-gray-500 dark:text-gray-400">Fecha: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Parties Grid -->
    <div class="grid grid-cols-2 gap-6 mb-8">
        <!-- Issuer -->
        <div class="border border-gray-200 dark:border-gray-700 p-4 rounded bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold mb-2">De (Emisor)</h3>
            <div class="font-bold text-base mb-1">{{ $data['issuer_name'] ?: '-' }}</div>
            @if($data['issuer_ruc'])
                <div>RUC: {{ $data['issuer_ruc'] }}@if($data['issuer_has_dv'] && $data['issuer_dv']) DV {{ $data['issuer_dv'] }}@endif</div>
            @endif
            @if($data['issuer_address'])<div class="mt-1">{{ $data['issuer_address'] }}</div>@endif
            @if($data['issuer_phone'])<div>Tel: {{ $data['issuer_phone'] }}</div>@endif
            @if($data['issuer_email'])<div>{{ $data['issuer_email'] }}</div>@endif
        </div>

        <!-- Recipient -->
        <div class="border border-gray-200 dark:border-gray-700 p-4 rounded bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold mb-2">Para (Destinatario)</h3>
            <div class="font-bold text-base mb-1">{{ $data['recipient_name'] ?: '-' }}</div>
            @if($data['recipient_ruc'])
                <div>RUC: {{ $data['recipient_ruc'] }}@if($data['recipient_has_dv'] && $data['recipient_dv']) DV {{ $data['recipient_dv'] }}@endif</div>
            @endif
            @if($data['recipient_address'])<div class="mt-1">{{ $data['recipient_address'] }}</div>@endif
            @if($data['recipient_phone'])<div>Tel: {{ $data['recipient_phone'] }}</div>@endif
            @if($data['recipient_email'])<div>{{ $data['recipient_email'] }}</div>@endif
        </div>
    </div>

    <!-- Items Table -->
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <th class="p-2 font-semibold w-16">Cant.</th>
                    <th class="p-2 font-semibold">Descripción</th>
                    <th class="p-2 font-semibold text-right w-32">P. Unit.</th>
                    <th class="p-2 font-semibold text-right w-24">ITBMS %</th>
                    <th class="p-2 font-semibold text-right w-32">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['items'] as $item)
                    @php
                        $qty = (float) ($item['quantity'] ?? 0);
                        $price = (float) ($item['unit_price'] ?? 0);
                        $taxRate = (float) ($item['tax_rate'] ?? 0);
                        $subtotal = $qty * $price;
                        $taxAmount = $subtotal * ($taxRate / 100);
                        $total = $subtotal + $taxAmount;
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="p-2">{{ number_format($qty, 2) }}</td>
                        <td class="p-2">{{ $item['description'] ?? '' }}</td>
                        <td class="p-2 text-right">{{ MoneyFormatter::format($price, $currency) }}</td>
                        <td class="p-2 text-right">{{ number_format($taxRate, 2) }}%</td>
                        <td class="p-2 text-right">{{ MoneyFormatter::format($total, $currency) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500 italic">No hay items agregados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="flex justify-end mb-8">
        <div class="w-72">
            <table class="w-full">
                <tr>
                    <td class="p-1 text-gray-600 dark:text-gray-400">Subtotal:</td>
                    <td class="p-1 text-right">{{ MoneyFormatter::format($data['totals']['subtotal'] ?? 0, $currency) }}</td>
                </tr>
                <tr>
                    <td class="p-1 text-gray-600 dark:text-gray-400">ITBMS:</td>
                    <td class="p-1 text-right">{{ MoneyFormatter::format($data['totals']['tax_amount'] ?? 0, $currency) }}</td>
                </tr>
                <tr class="font-bold text-lg border-t-2 border-gray-800 dark:border-gray-200">
                    <td class="pt-2 mt-2">Total:</td>
                    <td class="pt-2 mt-2 text-right">{{ MoneyFormatter::format($data['totals']['total'] ?? 0, $currency) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Footer Notes & Bank -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 text-xs text-gray-600 dark:text-gray-400">
        <div class="grid grid-cols-2 gap-6">
            <div>
                @if($data['bank_name'] || $data['bank_account_number'])
                    <div class="mb-2">
                        <strong class="text-gray-800 dark:text-gray-200">Datos Bancarios:</strong><br>
                        @if($data['bank_name']){{ $data['bank_name'] }}<br>@endif
                        @if($data['bank_account_number'])Cuenta: {{ $data['bank_account_number'] }}@endif
                    </div>
                @endif
                @if($data['yappy_id'])
                    <div>
                        <strong class="text-gray-800 dark:text-gray-200">Yappy:</strong> {{ $data['yappy_id'] }}
                    </div>
                @endif
            </div>
            <div>
                @if($data['footer_notes'])
                    <strong class="text-gray-800 dark:text-gray-200">Notas:</strong><br>
                    <div class="whitespace-pre-line">{{ $data['footer_notes'] }}</div>
                @endif
            </div>
        </div>
    </div>
</div>