@php
    $m = $this->getMetrics();
    $currency = $m['currency'];
    $hasItems = count($m['items']) > 0;
    $chartAsset = \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets');
    $barHeight = max(260, count($m['items_chart']['data']) * 34);
@endphp

<x-filament-panels::page>
    <style>
        .budget-metrics {
            --bm-ink: #0f172a;
            --bm-muted: #64748b;
            --bm-surface: rgba(15, 23, 42, 0.04);
            --bm-line: rgba(15, 23, 42, 0.08);
            --bm-amber: #f59e0b;
        }

        .dark .budget-metrics {
            --bm-ink: #f8fafc;
            --bm-muted: #94a3b8;
            --bm-surface: rgba(248, 250, 252, 0.04);
            --bm-line: rgba(248, 250, 252, 0.08);
        }

        .budget-metrics {
            display: grid;
            gap: 1rem;
        }

        .bm-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.25rem;
            min-height: 180px;
            padding: 1.5rem 1.75rem;
            background:
                radial-gradient(1200px 400px at 10% -20%, rgba(245, 158, 11, 0.32), transparent 55%),
                radial-gradient(900px 360px at 100% 0%, rgba(13, 148, 136, 0.22), transparent 50%),
                linear-gradient(145deg, #0f172a 0%, #1e293b 55%, #0b1220 100%);
            color: #f8fafc;
            animation: bm-fade 0.7s ease both;
        }

        .bm-hero__watermark {
            position: absolute;
            right: -0.5rem;
            bottom: -1.2rem;
            font-size: clamp(3.5rem, 10vw, 6.5rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            opacity: 0.12;
            pointer-events: none;
            white-space: nowrap;
        }

        .bm-hero__label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(248, 250, 252, 0.65);
        }

        .bm-hero__value {
            margin-top: 0.35rem;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 750;
            letter-spacing: -0.03em;
        }

        .bm-hero__pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .bm-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
        }

        .bm-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1100px) {
            .bm-grid-main {
                grid-template-columns: 1.15fr 0.85fr;
            }

            .bm-grid-side {
                grid-template-columns: 1fr 1fr;
            }
        }

        .bm-panel {
            border-radius: 1.15rem;
            border: 1px solid var(--bm-line);
            background: color-mix(in srgb, var(--bm-surface) 100%, transparent);
            padding: 1rem 1.1rem 1.15rem;
            animation: bm-rise 0.65s ease both;
        }

        .bm-panel:nth-child(2) { animation-delay: 0.05s; }
        .bm-panel:nth-child(3) { animation-delay: 0.1s; }
        .bm-panel:nth-child(4) { animation-delay: 0.15s; }

        .bm-panel__title {
            margin: 0 0 0.85rem;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--bm-muted);
        }

        .bm-treemap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            min-height: 280px;
            align-content: stretch;
        }

        .bm-tile {
            position: relative;
            overflow: hidden;
            border-radius: 0.9rem;
            min-width: 110px;
            min-height: 88px;
            padding: 0.75rem;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
            transform: translateY(8px);
            opacity: 0;
            animation: bm-tile 0.55s ease forwards;
        }

        .bm-tile__name {
            font-size: 0.78rem;
            font-weight: 650;
            line-height: 1.25;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .bm-tile__amount {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .bm-tile__meta {
            font-size: 0.68rem;
            opacity: 0.85;
        }

        .bm-empty {
            color: var(--bm-muted);
            font-size: 0.9rem;
            padding: 2rem 0.5rem;
            text-align: center;
        }

        @keyframes bm-fade {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes bm-rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes bm-tile {
            to { opacity: 1; transform: none; }
        }
    </style>

    <div class="budget-metrics">
        <section class="bm-hero">
            <div class="bm-hero__watermark">
                {{ \App\Support\MoneyFormatter::format($m['net_income'], $currency) }}
            </div>
            <div class="bm-hero__label">Ingreso del presupuesto</div>
            <div class="bm-hero__value">
                {{ \App\Support\MoneyFormatter::format($m['net_income'], $currency) }}
            </div>
            <div class="bm-hero__pills">
                <span class="bm-pill">
                    Asignado · {{ \App\Support\MoneyFormatter::format($m['total_allocated'], $currency) }}
                </span>
                <span class="bm-pill">
                    {{ $m['remaining_balance'] < 0 ? 'Excedido' : 'Libre' }} ·
                    {{ \App\Support\MoneyFormatter::format($m['remaining_balance'], $currency) }}
                </span>
                <span class="bm-pill">{{ count($m['items']) }} conceptos</span>
            </div>
        </section>

        @unless ($hasItems)
            <div class="bm-panel">
                <div class="bm-empty">Este presupuesto aún no tiene ítems para graficar.</div>
            </div>
        @else
            <div class="bm-grid bm-grid-main">
                <section class="bm-panel">
                    <h2 class="bm-panel__title">Mapa de montos por ítem</h2>
                    <div class="bm-treemap">
                        @foreach ($m['items'] as $index => $item)
                            @php
                                $flex = max((int) round($item['amount'] * 10), 12);
                            @endphp
                            <div
                                class="bm-tile"
                                style="
                                    flex: {{ $flex }} 1 {{ max(110, min(260, (int) ($item['share'] * 3))) }}px;
                                    background: linear-gradient(145deg, {{ $item['color'] }} 0%, color-mix(in srgb, {{ $item['color'] }} 70%, #0f172a) 100%);
                                    animation-delay: {{ 0.04 * $index }}s;
                                "
                            >
                                <div class="bm-tile__name">{{ $item['concept'] }}</div>
                                <div>
                                    <div class="bm-tile__amount">
                                        {{ \App\Support\MoneyFormatter::format($item['amount'], $currency) }}
                                    </div>
                                    <div class="bm-tile__meta">
                                        {{ number_format($item['share'], 1) }}% ·
                                        {{ $item['is_paid'] ? 'Pagado' : 'Pendiente' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="bm-panel">
                    <h2 class="bm-panel__title">Tendencia de monto (ranking)</h2>
                    <div
                        wire:ignore
                        x-load
                        x-load-src="{{ $chartAsset }}"
                        x-data="chart({
                            cachedData: {
                                labels: @js($m['ranking_chart']['labels']),
                                datasets: [{
                                    label: 'Monto',
                                    data: @js($m['ranking_chart']['data']),
                                    backgroundColor: @js($m['ranking_chart']['colors']),
                                    borderWidth: 0,
                                    borderRadius: 8,
                                    barThickness: 18,
                                }],
                            },
                            options: {
                                indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { beginAtZero: true, grid: { display: false } },
                                    y: { grid: { display: false } },
                                },
                            },
                            type: 'bar',
                        })"
                        class="fi-wi-chart-canvas-ctn fi-wi-chart-canvas-ctn-no-aspect-ratio"
                        style="max-height: {{ max(260, count($m['ranking_chart']['data']) * 38) }}px;"
                    >
                        <canvas x-ref="canvas" style="max-height: {{ max(260, count($m['ranking_chart']['data']) * 38) }}px;"></canvas>
                        <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                        <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                        <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                        <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                    </div>
                </section>
            </div>

            <div class="bm-grid bm-grid-side">
                <section class="bm-panel">
                    <h2 class="bm-panel__title">Composición</h2>
                    <div
                        wire:ignore
                        x-load
                        x-load-src="{{ $chartAsset }}"
                        x-data="chart({
                            cachedData: {
                                labels: @js($m['category_chart']['labels']),
                                datasets: [{
                                    label: 'Categoría',
                                    data: @js($m['category_chart']['data']),
                                    backgroundColor: @js($m['category_chart']['colors']),
                                    borderWidth: 0,
                                }],
                            },
                            options: {
                                plugins: { legend: { position: 'bottom' } },
                            },
                            type: 'polarArea',
                        })"
                        class="fi-wi-chart-canvas-ctn fi-wi-chart-canvas-ctn-no-aspect-ratio mx-auto"
                        style="max-height: 300px;"
                    >
                        <canvas x-ref="canvas" style="max-height: 300px;"></canvas>
                        <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                        <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                        <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                        <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                    </div>
                </section>

                <section class="bm-panel">
                    <h2 class="bm-panel__title">Pagado vs pendiente</h2>
                    <div
                        wire:ignore
                        x-load
                        x-load-src="{{ $chartAsset }}"
                        x-data="chart({
                            cachedData: {
                                labels: @js($m['payment_chart']['labels']),
                                datasets: [{
                                    label: 'Estado',
                                    data: @js($m['payment_chart']['data']),
                                    backgroundColor: @js($m['payment_chart']['colors']),
                                    borderWidth: 0,
                                    hoverOffset: 6,
                                }],
                            },
                            options: {
                                cutout: '68%',
                                plugins: { legend: { position: 'bottom' } },
                            },
                            type: 'doughnut',
                        })"
                        class="fi-wi-chart-canvas-ctn fi-wi-chart-canvas-ctn-no-aspect-ratio mx-auto"
                        style="max-height: 300px;"
                    >
                        <canvas x-ref="canvas" style="max-height: 300px;"></canvas>
                        <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                        <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                        <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                        <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                    </div>
                </section>
            </div>

            <section class="bm-panel">
                <h2 class="bm-panel__title">Todos los ítems · peso en el presupuesto</h2>
                <div
                    wire:ignore
                    x-load
                    x-load-src="{{ $chartAsset }}"
                    x-data="chart({
                        cachedData: {
                            labels: @js($m['items_chart']['labels']),
                            datasets: [{
                                label: 'Monto',
                                data: @js($m['items_chart']['data']),
                                backgroundColor: @js($m['items_chart']['colors']),
                                borderWidth: 0,
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true },
                                y: { ticks: { autoSkip: false } },
                            },
                        },
                        type: 'bar',
                    })"
                    class="fi-wi-chart-canvas-ctn fi-wi-chart-canvas-ctn-no-aspect-ratio"
                    style="max-height: {{ $barHeight }}px;"
                >
                    <canvas x-ref="canvas" style="max-height: {{ $barHeight }}px;"></canvas>
                    <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                    <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                    <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                    <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                </div>
            </section>
        @endunless
    </div>
</x-filament-panels::page>
