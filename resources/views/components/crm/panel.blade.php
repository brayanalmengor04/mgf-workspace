@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class(['mgf-crm-panel']) }}>
    <div class="mgf-crm-panel__header">
        <div>
            <h3 class="mgf-crm-panel__title">{{ $title }}</h3>
            @if (filled($subtitle))
                <p class="mgf-crm-panel__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div style="display:flex;gap:0.5rem;align-items:center;">
                {{ $actions }}
            </div>
        @endisset
    </div>
    <div class="mgf-crm-panel__body">
        {{ $slot }}
    </div>
</div>
