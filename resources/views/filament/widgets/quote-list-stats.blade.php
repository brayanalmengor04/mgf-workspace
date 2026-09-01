@php
    $drafts = $drafts ?? 0;
    $issued = $issued ?? 0;
    $cancelled = $cancelled ?? 0;
@endphp

<div class="mgf-crm mgf-crm-grid mgf-crm-grid--stats" style="margin-bottom:1rem;">
    <x-crm.stat-card label="Borradores" :value="(string) $drafts" />
    <x-crm.stat-card label="Emitidas" :value="(string) $issued" />
    <x-crm.stat-card label="Anuladas" :value="(string) $cancelled" />
</div>
