@props([
    'label',
    'value',
    'delta' => null,
    'deltaTone' => 'neutral',
    'sparkline' => [],
    'valueTone' => 'default',
])

@php
    $tone = is_array($delta) ? ($delta['tone'] ?? 'neutral') : $deltaTone;
    $deltaText = is_array($delta) ? ($delta['text'] ?? null) : $delta;
    $points = collect($sparkline)->map(fn ($v) => (float) $v)->filter(fn ($v) => is_finite($v))->values();

    $sparklinePath = '';
    if ($points->count() >= 2) {
        $min = $points->min();
        $max = $points->max();
        $range = max($max - $min, 0.0001);
        $width = 88;
        $height = 28;
        $step = $width / max($points->count() - 1, 1);

        $coords = $points->map(function (float $value, int $index) use ($min, $range, $step, $height): string {
            $x = round($index * $step, 2);
            $y = round($height - (($value - $min) / $range) * ($height - 4) - 2, 2);

            return "{$x},{$y}";
        })->implode(' ');

        $sparklinePath = $coords;
    }

    $strokeColor = match ($valueTone) {
        'success' => 'var(--mgf-crm-success)',
        'danger' => 'var(--mgf-crm-danger)',
        'warning' => 'var(--mgf-crm-warning)',
        default => 'var(--mgf-crm-accent)',
    };
@endphp

<div {{ $attributes->class(['mgf-crm-stat']) }}>
    <div class="mgf-crm-stat__top">
        <div class="mgf-crm-stat__copy">
            <div class="mgf-crm-stat__label">{{ $label }}</div>
            <div @class([
                'mgf-crm-stat__value',
                'mgf-crm-stat__value--success' => $valueTone === 'success',
                'mgf-crm-stat__value--danger' => $valueTone === 'danger',
                'mgf-crm-stat__value--warning' => $valueTone === 'warning',
            ])>{{ $value }}</div>
        </div>
        @if ($sparklinePath !== '')
            <svg class="mgf-crm-stat__sparkline" viewBox="0 0 88 28" aria-hidden="true">
                <polyline
                    fill="none"
                    stroke="{{ $strokeColor }}"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    points="{{ $sparklinePath }}"
                />
            </svg>
        @endif
    </div>
    @if (filled($deltaText))
        <div @class([
            'mgf-crm-stat__delta',
            'mgf-crm-stat__delta--up' => $tone === 'up',
            'mgf-crm-stat__delta--down' => $tone === 'down',
            'mgf-crm-stat__delta--neutral' => $tone === 'neutral',
        ])>
            {{ $deltaText }}
        </div>
    @endif
</div>
