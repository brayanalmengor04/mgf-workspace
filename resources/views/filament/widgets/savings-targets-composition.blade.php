@php
    $cards = $cards ?? [];

    $stateStyles = [
        'success' => [
            'ring' => 'rgba(16, 185, 129, 0.35)',
            'badge' => '#059669',
            'badge_text' => 'Al día',
        ],
        'warning' => [
            'ring' => 'rgba(245, 158, 11, 0.35)',
            'badge' => '#d97706',
            'badge_text' => 'Pendiente',
        ],
        'active' => [
            'ring' => 'rgba(59, 130, 246, 0.25)',
            'badge' => '#2563eb',
            'badge_text' => 'En progreso',
        ],
        'empty' => [
            'ring' => 'rgba(148, 163, 184, 0.25)',
            'badge' => '#64748b',
            'badge_text' => 'Sin meta',
        ],
    ];
@endphp

<x-filament-widgets::widget class="fi-wi-savings-composition">
    <x-filament::section heading="Metas y reposición" description="Meta del período, lo que te falta depositar y lo pendiente por reponer.">
        <div
            class="fi-wi-savings-composition-grid"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem;"
        >
            @foreach ($cards as $card)
                @php
                    $style = $stateStyles[$card['state']] ?? $stateStyles['active'];
                    $percent = min(100, max(0, (float) ($card['percent'] ?? 0)));
                @endphp

                <div
                    style="
                        border: 1px solid rgba(148, 163, 184, 0.25);
                        border-radius: 0.75rem;
                        padding: 1rem;
                        background: rgba(255, 255, 255, 0.02);
                        box-shadow: inset 0 0 0 1px {{ $style['ring'] }};
                    "
                >
                    <div style="display: flex; justify-content: space-between; gap: 0.5rem; align-items: start; margin-bottom: 0.5rem;">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $card['label'] }}</span>
                        <span
                            style="
                                font-size: 0.65rem;
                                font-weight: 700;
                                letter-spacing: 0.04em;
                                text-transform: uppercase;
                                color: #fff;
                                background: {{ $style['badge'] }};
                                border-radius: 9999px;
                                padding: 0.15rem 0.45rem;
                                white-space: nowrap;
                            "
                        >{{ $style['badge_text'] }}</span>
                    </div>

                    <div
                        class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white"
                        style="line-height: 1.2; margin-bottom: 0.75rem;"
                    >
                        {{ $card['value'] }}
                    </div>

                    <div
                        aria-hidden="true"
                        style="height: 0.55rem; border-radius: 9999px; background: rgba(148, 163, 184, 0.25); overflow: hidden; margin-bottom: 0.5rem;"
                    >
                        <div
                            style="height: 100%; width: {{ $percent }}%; background: {{ $card['bar_color'] }}; border-radius: 9999px;"
                        ></div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300" style="margin: 0; line-height: 1.45;">
                        {{ $card['hint'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
