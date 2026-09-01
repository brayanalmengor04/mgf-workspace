<x-filament-panels::page>
    @php
        $hub = $this->getHubData();
        $quote = $hub['quote'];
        $formatted = $hub['formatted'];
    @endphp

    <div class="mgf-crm" style="display:flex;flex-direction:column;gap:1.25rem;">
        <div class="mgf-crm-grid mgf-crm-grid--stats">
            <x-crm.stat-card label="Cliente" :value="$quote->recipient_name ?: 'Sin cliente'" />
            <x-crm.stat-card label="Estado" :value="$quote->status->label()" />
            <x-crm.stat-card label="Subtotal" :value="$formatted['subtotal']" />
            <x-crm.stat-card label="Total" :value="$formatted['total']" />
        </div>

        <x-crm.panel title="Detalle de la cotización">
            <div style="display:grid;gap:0.75rem;font-size:0.875rem;">
                <div><strong>Fecha:</strong> {{ $quote->quote_date?->format('d/m/Y') ?? '—' }}</div>
                <div><strong>Impuestos:</strong> {{ $formatted['tax'] }}</div>
                <div><strong>Emisor:</strong> {{ $quote->issuer_name ?: '—' }}</div>
                <div><strong>Destinatario:</strong> {{ $quote->recipient_email ?: '—' }}</div>
            </div>
        </x-crm.panel>
    </div>
</x-filament-panels::page>
