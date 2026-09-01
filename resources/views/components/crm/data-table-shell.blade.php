@props([
    'headers' => [],
    'rows' => [],
])

@php
    $renderCell = function (mixed $cell): string {
        if (is_array($cell)) {
            return match ($cell['type'] ?? 'text') {
                'link' => '<a href="'.e($cell['url'] ?? '#').'" class="mgf-crm-link">'.e($cell['label'] ?? '').'</a>',
                'badge' => '<span class="mgf-crm-badge mgf-crm-badge--'.e($cell['tone'] ?? 'gray').'">'.e($cell['label'] ?? '').'</span>',
                default => e($cell['value'] ?? $cell['text'] ?? ''),
            };
        }

        return e((string) $cell);
    };
@endphp

<div
    {{ $attributes->class(['mgf-crm-data-view']) }}
    x-data="{
        view: window.matchMedia('(max-width: 768px)').matches ? 'cards' : 'table',
    }"
    :class="view === 'cards' ? 'mgf-crm-data-view--cards' : 'mgf-crm-data-view--table'"
>
    <x-crm.data-view-toggle />

    <div class="mgf-crm-data-view__table" x-show="view === 'table'" x-cloak>
        <div class="mgf-crm-data-view__scroll">
            <table class="mgf-crm-table">
                @if (count($headers) > 0)
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{!! $renderCell($cell) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($headers)) }}" style="color:var(--mgf-crm-muted);">
                                Sin registros recientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mgf-crm-data-view__cards" x-show="view === 'cards'" x-cloak>
        @forelse ($rows as $row)
            <article class="mgf-crm-data-card">
                @foreach ($row as $index => $cell)
                    <div class="mgf-crm-data-card__row">
                        <span class="mgf-crm-data-card__label">{{ $headers[$index] ?? '' }}</span>
                        <span class="mgf-crm-data-card__value">{!! $renderCell($cell) !!}</span>
                    </div>
                @endforeach
            </article>
        @empty
            <p class="mgf-crm-data-card__empty">Sin registros recientes.</p>
        @endforelse
    </div>
</div>
