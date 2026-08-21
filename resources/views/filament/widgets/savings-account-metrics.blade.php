@php
    $accounts = $accounts ?? [];
    $metrics = $metrics ?? null;
    $projectionCadenceOptions = $projection_cadence_options ?? [];
    $projectionCadence = $projection_cadence ?? 'biweekly';
@endphp

<x-filament-widgets::widget class="fi-wi-savings-metrics">
    <x-filament::section>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div
                style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;"
            >
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; flex: 1 1 24rem;">
                    <div style="flex: 1 1 16rem; min-width: 14rem;">
                        <label class="text-sm font-medium text-gray-950 dark:text-white" style="display: block; margin-bottom: 0.35rem;">
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
                        <label class="text-sm font-medium text-gray-950 dark:text-white" style="display: block; margin-bottom: 0.35rem;">
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
                                background: conic-gradient(#10b981 {{ min(100, max(0, $metrics['health_score'])) }}%, rgba(148,163,184,0.25) 0);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                position: relative;
                            "
                            title="Salud del ahorro"
                        >
                            <div
                                style="
                                    width: 3.4rem;
                                    height: 3.4rem;
                                    border-radius: 9999px;
                                    background: rgb(255 255 255 / 0.95);
                                    display: flex;
                                    flex-direction: column;
                                    align-items: center;
                                    justify-content: center;
                                    line-height: 1;
                                "
                                class="dark:!bg-gray-900"
                            >
                                <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $metrics['health_score'] }}</span>
                                <span style="font-size: 0.55rem; text-transform: uppercase; letter-spacing: 0.04em;" class="text-gray-500 dark:text-gray-400">salud</span>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Saldo actual</div>
                            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $metrics['balance_formatted'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $metrics['health_label'] }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($metrics === null)
                <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: 0;">
                    Crea una cuenta de ahorro para ver tus metas aquí.
                </p>
            @else
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 0.75rem;"
                >
                    <div style="border: 1px solid rgba(148,163,184,0.25); border-radius: 0.65rem; padding: 0.75rem;">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Flujo neto</div>
                        <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $metrics['net_flow_formatted'] }}</div>
                    </div>
                    <div style="border: 1px solid rgba(148,163,184,0.25); border-radius: 0.65rem; padding: 0.75rem;">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Racha</div>
                        <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $metrics['streak_months'] }} mes(es)</div>
                    </div>
                    @if ($metrics['period_progress_percent'] !== null)
                        <div style="border: 1px solid rgba(59,130,246,0.25); border-radius: 0.65rem; padding: 0.75rem; background: rgba(59,130,246,0.04);">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Período {{ $metrics['period_label'] }}</div>
                            <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ number_format($metrics['period_progress_percent'], 1) }}%</div>
                        </div>
                    @endif
                    @if (filled($metrics['projection_date'] ?? null))
                        <div style="border: 1px solid rgba(16,185,129,0.25); border-radius: 0.65rem; padding: 0.75rem; background: rgba(16,185,129,0.04);">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Meta estimada ({{ $metrics['projection_cadence_label'] ?? 'Quincenal' }})</div>
                            <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $metrics['projection_date'] }}</div>
                            @if (filled($metrics['projection_frequency'] ?? null))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $metrics['projection_frequency'] }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if ($metrics !== null && ! $metrics['has_goal'])
                <div
                    style="
                        border: 1px dashed rgba(148, 163, 184, 0.45);
                        border-radius: 0.75rem;
                        padding: 1rem;
                    "
                >
                    <p class="text-sm text-gray-600 dark:text-gray-300" style="margin: 0;">
                        Esta cuenta no tiene meta configurada. Edítala y define <strong>Meta total</strong> o <strong>Monto meta por período</strong>.
                    </p>
                </div>
            @elseif ($metrics !== null)
                @if ($metrics['period_progress_percent'] !== null)
                    <div
                        style="
                            border: 1px solid rgba(99, 102, 241, 0.25);
                            border-radius: 0.75rem;
                            padding: 1rem;
                            background: rgba(99, 102, 241, 0.04);
                        "
                    >
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; margin-bottom: 0.5rem;">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Ritmo {{ $metrics['period_label'] }}</span>
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ number_format($metrics['period_progress_percent'], 1) }}%</span>
                        </div>
                        <div
                            aria-hidden="true"
                            style="height: 0.55rem; border-radius: 9999px; background: rgba(148, 163, 184, 0.25); overflow: hidden; margin-bottom: 0.5rem;"
                        >
                            <div
                                style="height: 100%; width: {{ min(100, max(0, $metrics['period_progress_percent'])) }}%; background: #6366f1; border-radius: 9999px;"
                            ></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300" style="margin: 0;">
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
                <div
                    style="
                        border: 1px solid rgba(245, 158, 11, 0.35);
                        border-radius: 0.75rem;
                        padding: 1rem;
                        background: rgba(245, 158, 11, 0.05);
                    "
                >
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; margin-bottom: 0.5rem;">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Por reponer</span>
                        <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ $metrics['pending_formatted'] }}</span>
                    </div>

                    <div
                        aria-hidden="true"
                        style="height: 0.65rem; border-radius: 9999px; background: rgba(148, 163, 184, 0.25); overflow: hidden; margin-bottom: 0.65rem;"
                    >
                        <div
                            style="height: 100%; width: {{ min(100, max(0, $metrics['replenishment_progress_percent'] ?? 0)) }}%; background: #f59e0b; border-radius: 9999px;"
                        ></div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300" style="margin: 0;">
                        Has repuesto {{ number_format($metrics['replenishment_progress_percent'] ?? 0, 1) }}%.
                        Usa <strong>Reponer</strong> en la fila de la cuenta para registrar la reposición.
                    </p>
                </div>
            @elseif ($metrics !== null)
                <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: 0;">
                    Todo repuesto. Tu saldo refleja lo acumulado sin pendientes.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
