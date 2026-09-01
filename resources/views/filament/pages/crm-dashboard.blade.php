<x-filament-panels::page>
    @php
        $crm = $crm ?? [];
        $statSections = $crm['stat_sections'] ?? [];
        $trend = $crm['trend'] ?? ['labels' => [], 'available_balance' => [], 'net_income' => [], 'paid_amount' => []];
        $trendData = match ($trendMetric) {
            'net_income' => $trend['net_income'] ?? [],
            'paid_amount' => $trend['paid_amount'] ?? [],
            default => $trend['available_balance'] ?? [],
        };
        $trendLabel = match ($trendMetric) {
            'net_income' => 'Ingreso neto',
            'paid_amount' => 'Pagado',
            default => 'Saldo disponible',
        };
        $trendLabels = $trend['labels'] ?? [];
        $categoryChart = $crm['category_chart'] ?? ['labels' => [], 'data' => [], 'colors' => []];
        $paymentSplit = $crm['payment_split'] ?? ['paid' => 0, 'pending' => 0];
        $trendPointDeltas = $crm['trend_point_deltas'] ?? [];
        $currentTrendDeltas = match ($trendMetric) {
            'net_income' => $trendPointDeltas['net_income'] ?? [],
            'paid_amount' => $trendPointDeltas['paid_amount'] ?? [],
            default => $trendPointDeltas['available_balance'] ?? [],
        };
        $paymentProgress = $crm['payment_progress'] ?? [];
        $trendPointCount = max(count($trendLabels), count($currentTrendDeltas) + 1);

        $pendingBudgetRows = collect($crm['pending_budget_items'] ?? [])->map(fn (array $item): array => [
            ['type' => 'text', 'value' => $item['concept'] ?? ''],
            ['type' => 'text', 'value' => $item['amount'] ?? ''],
            ['type' => 'text', 'value' => $item['category'] ?? ''],
            ['type' => 'text', 'value' => $item['budget'] ?? ''],
        ])->all();

        $recentBudgetRows = collect($crm['recent_budgets'] ?? [])->map(fn (array $row): array => [
            ['type' => 'link', 'url' => $row['url'] ?? '#', 'label' => $row['number'] ?? ''],
            ['type' => 'text', 'value' => $row['title'] ?? ''],
            ['type' => 'text', 'value' => $row['status'] ?? ''],
            ['type' => 'text', 'value' => $row['available'] ?? ''],
        ])->all();

        $recentQuoteRows = collect($crm['recent_quotes'] ?? [])->map(fn (array $row): array => [
            ['type' => 'link', 'url' => $row['url'] ?? '#', 'label' => $row['number'] ?? ''],
            ['type' => 'text', 'value' => $row['client'] ?? ''],
            ['type' => 'text', 'value' => $row['status'] ?? ''],
            ['type' => 'text', 'value' => $row['total'] ?? ''],
        ])->all();
    @endphp

    <div class="mgf-crm" style="display:flex;flex-direction:column;gap:1.25rem;">
        @foreach ($statSections as $section)
            <div class="mgf-crm-stat-section">
                <div class="mgf-crm-stat-section__header">
                    <h2 class="mgf-crm-stat-section__title">{{ $section['title'] ?? '' }}</h2>
                    @if (! empty($section['subtitle']))
                        <p class="mgf-crm-stat-section__subtitle">{{ $section['subtitle'] }}</p>
                    @endif
                </div>
                <div class="mgf-crm-grid mgf-crm-grid--stats">
                    @foreach ($section['stats'] ?? [] as $stat)
                        <x-crm.stat-card
                            :label="$stat['label']"
                            :value="$stat['value']"
                            :delta="$stat['delta'] ?? null"
                            :sparkline="$stat['sparkline'] ?? []"
                            :value-tone="$stat['value_tone'] ?? 'default'"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach

        <livewire:budget-scan-widget />

        <x-crm.panel title="Estadísticas" subtitle="Evolución por presupuesto emitido">
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;">
                <button
                    type="button"
                    wire:click="$set('trendMetric', 'available_balance')"
                    @class(['mgf-crm-tab', 'mgf-crm-tab--active' => $trendMetric === 'available_balance'])
                >Saldo disponible</button>
                <button
                    type="button"
                    wire:click="$set('trendMetric', 'net_income')"
                    @class(['mgf-crm-tab', 'mgf-crm-tab--active' => $trendMetric === 'net_income'])
                >Ingreso neto</button>
                <button
                    type="button"
                    wire:click="$set('trendMetric', 'paid_amount')"
                    @class(['mgf-crm-tab', 'mgf-crm-tab--active' => $trendMetric === 'paid_amount'])
                >Pagado</button>
            </div>
            <div style="position:relative;">
                <x-crm.chart id="crm-trend-chart" height="18rem" />
                @if (count($currentTrendDeltas) > 0 && $trendPointCount > 1)
                    <div
                        id="crm-trend-deltas"
                        aria-label="Variaciones entre presupuestos"
                        style="
                            position: absolute;
                            left: 3%;
                            right: 1%;
                            top: 12%;
                            bottom: 28%;
                            display: flex;
                            align-items: center;
                            padding-left: calc(100% / {{ $trendPointCount }} / 2);
                            padding-right: calc(100% / {{ $trendPointCount }} / 2);
                            pointer-events: none;
                            z-index: 5;
                        "
                    >
                        @foreach ($currentTrendDeltas as $delta)
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
                                <span
                                    style="
                                        color: {{ $delta['color'] }};
                                        background: {{ $delta['background'] }};
                                        border: 1px solid {{ $delta['border'] ?? $delta['background'] }};
                                        font-size: 0.72rem;
                                        font-weight: 700;
                                        line-height: 1.2;
                                        padding: 0.2rem 0.45rem;
                                        border-radius: 9999px;
                                        white-space: nowrap;
                                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
                                    "
                                >{{ $delta['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-crm.panel>

        <div class="mgf-crm-grid mgf-crm-grid--2">
            <x-crm.panel title="Composición del período" subtitle="Distribución del último presupuesto">
                <x-crm.chart id="crm-category-chart" height="17rem" />
            </x-crm.panel>

            <x-crm.panel title="Cumplimiento" subtitle="Metas y pagos del período">
                <div class="mgf-crm-grid mgf-crm-grid--stats" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <x-crm.chart id="crm-gauge-paid" height="11rem" />
                    <x-crm.chart id="crm-gauge-savings" height="11rem" />
                    <x-crm.chart id="crm-gauge-fixed" height="11rem" />
                </div>
            </x-crm.panel>
        </div>

        <x-crm.panel title="Flujo de pagos" subtitle="Pagado vs pendiente en presupuestos emitidos">
            <x-crm.chart id="crm-payment-split-chart" height="7rem" />
        </x-crm.panel>

        <div class="mgf-crm-grid mgf-crm-grid--2">
            <x-crm.panel title="Próximos eventos" subtitle="Calendario financiero">
                <x-slot:actions>
                    <a href="{{ \App\Filament\Pages\FinancialCalendar::getUrl() }}" class="mgf-crm-tab">Ver calendario</a>
                </x-slot:actions>
                <x-crm.schedule-list :events="$crm['upcoming_events'] ?? []" />
            </x-crm.panel>

            <x-crm.panel title="Pendientes por pagar" subtitle="Ítems de presupuestos emitidos">
                <x-crm.data-table-shell
                    :headers="['Concepto', 'Monto', 'Categoría', 'Presupuesto']"
                    :rows="$pendingBudgetRows"
                />
            </x-crm.panel>
        </div>

        <div class="mgf-crm-grid mgf-crm-grid--2">
            <x-crm.panel title="Presupuestos recientes">
                <x-crm.data-table-shell
                    :headers="['Número', 'Título', 'Estado', 'Disponible']"
                    :rows="$recentBudgetRows"
                />
            </x-crm.panel>

            <x-crm.panel title="Cotizaciones recientes">
                <x-crm.data-table-shell
                    :headers="['Número', 'Cliente', 'Estado', 'Total']"
                    :rows="$recentQuoteRows"
                />
            </x-crm.panel>
        </div>
    </div>

    @script
    <script>
        const charts = window.MgfCrmCharts;

        const renderCrmDashboard = () => {
            if (! charts) return;

            charts.renderTrend('crm-trend-chart', {
                labels: @js($trendLabels),
                data: @js($trendData),
                label: @js($trendLabel),
            });

            charts.renderRose('crm-category-chart', @js($categoryChart));

            const progressItems = @js($paymentProgress);
            if (progressItems[0]) {
                charts.renderGauges('crm-gauge-paid', { items: [progressItems[0]] });
            }
            if (progressItems[1]) {
                charts.renderGauges('crm-gauge-savings', { items: [progressItems[1]] });
            }
            if (progressItems[2]) {
                charts.renderGauges('crm-gauge-fixed', { items: [progressItems[2]] });
            }

            charts.renderPaidSplit('crm-payment-split-chart', @js($paymentSplit));
        };

        const updateTrendDeltas = (deltas, pointCount) => {
            const container = document.getElementById('crm-trend-deltas');
            if (! container) return;

            if (! deltas || deltas.length === 0 || pointCount <= 1) {
                container.innerHTML = '';
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';
            container.style.paddingLeft = `calc(100% / ${pointCount} / 2)`;
            container.style.paddingRight = `calc(100% / ${pointCount} / 2)`;
            container.innerHTML = deltas.map((delta) => `
                <div style="flex:1;display:flex;justify-content:center;align-items:center;">
                    <span style="
                        color:${delta.color};
                        background:${delta.background};
                        border:1px solid ${delta.border ?? delta.background};
                        font-size:0.72rem;font-weight:700;line-height:1.2;
                        padding:0.2rem 0.45rem;border-radius:9999px;white-space:nowrap;
                        box-shadow:0 1px 3px rgba(0,0,0,0.25);
                    ">${delta.label}</span>
                </div>
            `).join('');
        };

        document.addEventListener('livewire:navigated', renderCrmDashboard);

        $wire.on('crm-trend-changed', ({ labels, data, label, deltas, pointCount }) => {
            const el = document.getElementById('crm-trend-chart');
            if (! el || ! charts) return;

            const chart = charts.getChart(el);
            if (chart) {
                chart.setOption({
                    xAxis: { data: labels },
                    series: [{ data, name: label ?? 'Tendencia' }],
                });
            } else {
                charts.renderTrend(el, { labels, data, label });
            }

            updateTrendDeltas(deltas ?? [], pointCount ?? labels?.length ?? 0);
        });

        renderCrmDashboard();
    </script>
    @endscript
</x-filament-panels::page>
