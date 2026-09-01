@props([
    'events' => [],
])

<div {{ $attributes->class(['mgf-crm']) }}>
    @forelse ($events as $event)
        <div class="mgf-crm-schedule-item">
            <div class="mgf-crm-schedule-item__date">{{ $event['date'] ?? '—' }}</div>
            <div>
                <p class="mgf-crm-schedule-item__title">{{ $event['title'] ?? '' }}</p>
                @if (filled($event['meta'] ?? null))
                    <p class="mgf-crm-schedule-item__meta">{{ $event['meta'] }}</p>
                @endif
            </div>
        </div>
    @empty
        <p style="margin:0;font-size:0.8125rem;color:var(--mgf-crm-muted);">No hay eventos próximos.</p>
    @endforelse
</div>
