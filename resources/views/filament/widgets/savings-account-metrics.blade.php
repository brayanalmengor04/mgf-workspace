@php
    $accounts = $accounts ?? [];
    $metrics = $metrics ?? null;
    $projectionCadenceOptions = $projection_cadence_options ?? [];
    $projectionCadence = $projection_cadence ?? 'biweekly';
@endphp

<x-filament-widgets::widget class="fi-wi-savings-metrics mgf-crm">
    <x-filament::section>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div
                style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;"
            >
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; flex: 1 1 24rem;">
                    <div style="flex: 1 1 16rem; min-width: 14rem;">
                        <label class="mgf-savings-field-label" style="display: block; margin-bottom: 0.35rem;">
                            Cuenta de ahorro
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedAccountId">
                                @forelse ($accounts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @empty
                                    <option value="">Sin cuentas activas</option>
                                @endforelse
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    <div style="flex: 1 1 12rem; min-width: 11rem;">
                        <label class="mgf-savings-field-label" style="display: block; margin-bottom: 0.35rem;">
                            Proyección
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="projectionCadence">
                                @foreach ($projectionCadenceOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                @if ($metrics !== null)
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div
                            style="
                                width: 4.5rem;
                                height: 4.5rem;
                                border-radius: 9999px;
                                background: conic-gradient(#10b981 {{ min(100, max(0, $metrics['health_score'])) }}%, color-mix(in oklab, var(--mgf-crm-muted) 35%, transparent) 0);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                position: relative;
                            "
                            title="Salud del ahorro"
                        >
                            <div class="mgf-savings-health-ring__inner">
                                <span class="mgf-savings-health-score">{{ $metrics['health_score'] }}</span>
                                <span class="mgf-savings-health-label">salud</span>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div class="mgf-savings-field-label">Saldo actual</div>
                            <div class="mgf-savings-balance-value">{{ $metrics['balance_formatted'] }}</div>
                            <div class="mgf-savings-field-hint">{{ $metrics['health_label'] }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($metrics === null)
                <p class="mgf-savings-field-hint" style="margin: 0;">
                    Crea una cuenta de ahorro para ver tus metas aquí.
                </p>
            @else
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 0.75rem;"
                >
                    <div class="mgf-savings-mini-card">
                        <div class="mgf-savings-field-label">Flujo neto</div>
                        <div class="mgf-savings-mini-card__value">{{ $metrics['net_flow_formatted'] }}</div>
                    </div>
                    <div class="mgf-savings-mini-card">
                        <div class="mgf-savings-field-label">Racha</div>
                        <div class="mgf-savings-mini-card__value">{{ $metrics['streak_months'] }} mes(es)</div>
                    </div>
                    @if ($metrics['period_progress_percent'] !== null)
                        <div class="mgf-savings-mini-card mgf-savings-mini-card--info">
                            <div class="mgf-savings-field-label">Período {{ $metrics['period_label'] }}</div>
                            <div class="mgf-savings-mini-card__value">{{ number_format($metrics['period_progress_percent'], 1) }}%</div>
                        </div>
                    @endif
                    @if (filled($metrics['projection_date'] ?? null))
                        <div class="mgf-savings-mini-card mgf-savings-mini-card--success">
                            <div class="mgf-savings-field-label">Meta estimada ({{ $metrics['projection_cadence_label'] ?? 'Quincenal' }})</div>
                            <div class="mgf-savings-mini-card__value">{{ $metrics['projection_date'] }}</div>
                            @if (filled($metrics['projection_frequency'] ?? null))
                                <div class="mgf-savings-field-hint">{{ $metrics['projection_frequency'] }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if ($metrics !== null && ! $metrics['has_goal'])
                <div class="mgf-savings-callout">
                    <p class="mgf-savings-callout__text">
                        Esta cuenta no tiene meta configurada. Edítala y define <strong>Meta total</strong> o <strong>Monto meta por período</strong>.
                    </p>
                </div>
            @elseif ($metrics !== null)
                @if ($metrics['period_progress_percent'] !== null)
                    <div class="mgf-savings-callout mgf-savings-callout--pace">
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; margin-bottom: 0.5rem;">
                            <span class="mgf-savings-callout__title">Ritmo {{ $metrics['period_label'] }}</span>
                            <span class="mgf-savings-callout__title">{{ number_format($metrics['period_progress_percent'], 1) }}%</span>
                        </div>
                        <div class="mgf-savings-progress-track" aria-hidden="true">
                            <div
                                class="mgf-savings-progress-fill mgf-savings-progress-fill--accent"
                                style="width: {{ min(100, max(0, $metrics['period_progress_percent'])) }}%;"
                            ></div>
                        </div>
                        <p class="mgf-savings-callout__text">
                            Llevas <strong>{{ $metrics['period_deposits_formatted'] }}</strong>
                            de <strong>{{ $metrics['period_target_formatted'] }}</strong> este período.
                            @if (($metrics['pace_status'] ?? '') === 'ahead')
                                Vas adelantado.
                            @elseif (($metrics['pace_status'] ?? '') === 'behind')
                                Faltan <strong>{{ $metrics['period_remaining_formatted'] }}</strong>.
                            @endif
                        </p>
                    </div>
                @endif
            @endif

            @if ($metrics !== null && ($metrics['pending_replenishment'] ?? 0) > 0)
                <div class="mgf-savings-callout mgf-savings-callout--warning">
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; margin-bottom: 0.5rem;">
                        <span class="mgf-savings-callout__title">Por reponer</span>
                        <span class="mgf-savings-callout__title" style="color: var(--mgf-crm-warning);">{{ $metrics['pending_formatted'] }}</span>
                    </div>

                    <div class="mgf-savings-progress-track mgf-savings-progress-track--lg" aria-hidden="true">
                        <div
                            class="mgf-savings-progress-fill mgf-savings-progress-fill--warning"
                            style="width: {{ min(100, max(0, $metrics['replenishment_progress_percent'] ?? 0)) }}%;"
                        ></div>
                    </div>

                    <p class="mgf-savings-callout__text">
                        Has repuesto {{ number_format($metrics['replenishment_progress_percent'] ?? 0, 1) }}%.
                        Usa <strong>Reponer</strong> en la fila de la cuenta para registrar la reposición.
                    </p>
                </div>
            @elseif ($metrics !== null)
                <p class="mgf-savings-field-hint" style="margin: 0;">
                    Todo repuesto. Tu saldo refleja lo acumulado sin pendientes.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
