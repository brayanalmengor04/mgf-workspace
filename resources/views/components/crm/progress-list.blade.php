@props([
    'items' => [],
])

<div {{ $attributes->class(['mgf-crm']) }}>
    @forelse ($items as $item)
        <div class="mgf-crm-progress-item">
            <div class="mgf-crm-progress-item__row">
                <span>{{ $item['label'] ?? '' }}</span>
                <strong>{{ number_format((float) ($item['percent'] ?? 0), 1) }}%</strong>
            </div>
            <div class="mgf-crm-progress-item__bar">
                <div
                    class="mgf-crm-progress-item__fill"
                    style="width: {{ min(100, max(0, (float) ($item['percent'] ?? 0))) }}%;"
                ></div>
            </div>
        </div>
    @empty
        <p style="margin:0;font-size:0.8125rem;color:var(--mgf-crm-muted);">Sin datos para mostrar.</p>
    @endforelse
</div>
