<x-filament-panels::page>
    @php
        $hub = $this->getHubData();
        $plan = $hub['plan'];
        $metrics = $hub['metrics'];
        $formatted = $hub['formatted'];

        $itemRows = collect($metrics['items'])->map(fn (array $item): array => [
            ['type' => 'text', 'value' => $item['concept']],
            ['type' => 'text', 'value' => $item['category']],
            ['type' => 'text', 'value' => number_format((float) $item['amount'], 2)],
            $item['is_paid']
                ? ['type' => 'badge', 'label' => 'Pagado', 'tone' => 'success']
                : ['type' => 'badge', 'label' => 'Pendiente', 'tone' => 'warning'],
        ])->all();
    @endphp

    <div class="mgf-crm" style="display:flex;flex-direction:column;gap:1.25rem;">
        <div class="mgf-crm-grid mgf-crm-grid--stats">
            <x-crm.stat-card label="Ingreso neto" :value="$formatted['net_income']" />
            <x-crm.stat-card label="Asignado" :value="$formatted['allocated']" />
            <x-crm.stat-card label="Disponible" :value="$formatted['remaining']" />
            <x-crm.stat-card
                label="Pagado"
                :value="$formatted['paid']"
                :delta="['text' => $metrics['payment_percent'].'% del plan', 'tone' => $metrics['payment_percent'] >= 70 ? 'up' : 'neutral']"
            />
        </div>

        <div class="mgf-crm-tabs">
            @foreach ([
                'summary' => 'Resumen',
                'items' => 'Partidas',
                'documents' => 'Documentos',
            ] as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    @class(['mgf-crm-tab', 'mgf-crm-tab--active' => $activeTab === $tab])
                >{{ $label }}</button>
            @endforeach
        </div>

        @if ($activeTab === 'summary')
            <div class="mgf-crm-grid mgf-crm-grid--2">
                <x-crm.panel title="Composición por categoría">
                    <x-crm.chart id="budget-category-chart" height="18rem" />
                </x-crm.panel>
                <x-crm.panel title="Pagado vs pendiente">
                    <x-crm.chart id="budget-payment-chart" height="14rem" />
                    <x-crm.chart id="budget-payment-gauge" height="10rem" style="margin-top:0.5rem;" />
                    <p style="margin:1rem 0 0;font-size:0.8125rem;color:var(--mgf-crm-muted);">
                        Pendiente por pagar: <strong>{{ $formatted['pending'] }}</strong>
                    </p>
                </x-crm.panel>
            </div>
        @endif

        @if ($activeTab === 'items')
            <x-crm.panel title="Partidas del presupuesto" subtitle="Estado de pago por concepto">
                <x-crm.data-table-shell
                    :headers="['Concepto', 'Categoría', 'Monto', 'Estado']"
                    :rows="$itemRows"
                />
            </x-crm.panel>
        @endif

        @if ($activeTab === 'documents')
            <x-crm.panel title="Vista previa del documento">
                @if ($plan->pdf_path)
                    <iframe
                        src="{{ \App\Filament\Resources\BudgetPlans\BudgetPlanResource::getUrl('preview', ['record' => $plan]) }}"
                        style="width:100%;min-height:70vh;border:1px solid var(--mgf-crm-card-border);border-radius:0.75rem;"
                        title="Vista previa PDF"
                    ></iframe>
                @else
                    <p style="margin:0;color:var(--mgf-crm-muted);font-size:0.875rem;">
                        Aún no hay PDF generado. Edita el presupuesto y genera el documento desde las acciones del editor.
                    </p>
                @endif
            </x-crm.panel>
        @endif
    </div>

    @script
    <script>
        const charts = window.MgfCrmCharts;

        const renderBudgetCharts = () => {
            if (! charts) return;

            if (! document.getElementById('budget-category-chart')) return;

            const category = @js($metrics['category_chart']);
            const payment = @js($metrics['payment_chart']);
            const paymentPercent = @js($metrics['payment_percent']);

            charts.renderDonut('budget-category-chart', category);

            charts.renderPaidSplit('budget-payment-chart', {
                paid: payment.data[0] ?? 0,
                pending: payment.data[1] ?? 0,
                labels: payment.labels ?? ['Pagado', 'Pendiente'],
            });

            charts.renderGauges('budget-payment-gauge', {
                items: [{ label: 'Cumplimiento de pagos', percent: paymentPercent }],
            });
        };

        document.addEventListener('livewire:navigated', renderBudgetCharts);
        $wire.on('budget-hub-charts', renderBudgetCharts);
        renderBudgetCharts();
    </script>
    @endscript
</x-filament-panels::page>
