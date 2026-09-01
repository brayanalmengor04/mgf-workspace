<x-filament-panels::page>
    <div class="mgf-crm mgf-crm-review-scan" style="display:flex;flex-direction:column;gap:1.25rem;">
        @if (count($warnings) > 0)
            <x-crm.panel title="Alertas del escaneo" subtitle="Confianza global: {{ number_format($extraction_confidence * 100, 0) }}%">
                <ul style="margin:0;padding-left:1.1rem;font-size:0.8125rem;color:var(--mgf-crm-muted);">
                    @foreach ($warnings as $warning)
                        <li style="margin-bottom:0.35rem;">{{ $warning }}</li>
                    @endforeach
                </ul>
            </x-crm.panel>
        @endif

        <x-crm.panel title="Datos del presupuesto" subtitle="Ajusta lo detectado antes de crear el borrador">
            <div class="mgf-crm-review-scan__form-grid">
                <div>
                    <label class="mgf-crm-review-scan__label">Título</label>
                    <input type="text" wire:model="budgetTitle" class="mgf-crm-review-scan__input">
                    @error('budgetTitle') <span class="mgf-crm-review-scan__error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mgf-crm-review-scan__label">Periodo</label>
                    <select wire:model="period" class="mgf-crm-review-scan__input">
                        @foreach ($this->periodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mgf-crm-review-scan__label">Moneda</label>
                    <select wire:model="currency" class="mgf-crm-review-scan__input">
                        @foreach ($this->currencyOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mgf-crm-review-scan__label">Ingreso neto</label>
                    <input type="number" step="0.01" min="0" wire:model="net_income" class="mgf-crm-review-scan__input">
                    @error('net_income') <span class="mgf-crm-review-scan__error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div style="margin-top:0.75rem;">
                <label class="mgf-crm-review-scan__label">Notas de ingreso</label>
                <input type="text" wire:model="income_notes" class="mgf-crm-review-scan__input">
            </div>
        </x-crm.panel>

        <x-crm.panel title="Ítems detectados" subtitle="Suma: {{ number_format($this->itemsTotal, 2) }} — Disponible: {{ number_format($net_income - $this->itemsTotal, 2) }}">
            <div
                class="mgf-crm-data-view"
                x-data="{ view: window.matchMedia('(max-width: 768px)').matches ? 'cards' : 'table' }"
                :class="view === 'cards' ? 'mgf-crm-data-view--cards' : 'mgf-crm-data-view--table'"
            >
                <x-crm.data-view-toggle />

                <div class="mgf-crm-data-view__table" x-show="view === 'table'" x-cloak>
                    <div class="mgf-crm-data-view__scroll">
                        <table class="mgf-crm-table">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Categoría</th>
                                    <th>Monto</th>
                                    <th>Conf.</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $index => $item)
                                    <tr @class(['mgf-crm-review-scan__row--warn' => !empty($item['needs_amount'])])>
                                        <td>
                                            <input type="text" wire:model="items.{{ $index }}.concept" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                            @if (!empty($item['matched_template']))
                                                <span class="mgf-crm-badge mgf-crm-badge--gray" style="margin-top:4px;">Frecuente</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select wire:model="items.{{ $index }}.category_type" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                                @foreach ($this->categoryOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" wire:model="items.{{ $index }}.amount" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                            @if (!empty($item['needs_amount']))
                                                <span class="mgf-crm-review-scan__error">Monto requerido</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format((float) ($item['confidence'] ?? 0) * 100, 0) }}%</td>
                                        <td>
                                            <button type="button" wire:click="removeItem({{ $index }})" class="mgf-crm-review-scan__remove">Quitar</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="color:var(--mgf-crm-muted);">No se detectaron ítems. Descarta y vuelve a escanear.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mgf-crm-data-view__cards" x-show="view === 'cards'" x-cloak>
                    @forelse ($items as $index => $item)
                        <article @class(['mgf-crm-data-card', 'mgf-crm-review-scan__card--warn' => !empty($item['needs_amount'])])>
                            <div class="mgf-crm-data-card__row">
                                <span class="mgf-crm-data-card__label">Concepto</span>
                                <span class="mgf-crm-data-card__value">
                                    <input type="text" wire:model="items.{{ $index }}.concept" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                    @if (!empty($item['matched_template']))
                                        <span class="mgf-crm-badge mgf-crm-badge--gray" style="margin-top:4px;">Frecuente</span>
                                    @endif
                                </span>
                            </div>
                            <div class="mgf-crm-data-card__row">
                                <span class="mgf-crm-data-card__label">Categoría</span>
                                <span class="mgf-crm-data-card__value">
                                    <select wire:model="items.{{ $index }}.category_type" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                        @foreach ($this->categoryOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </span>
                            </div>
                            <div class="mgf-crm-data-card__row">
                                <span class="mgf-crm-data-card__label">Monto</span>
                                <span class="mgf-crm-data-card__value">
                                    <input type="number" step="0.01" min="0" wire:model="items.{{ $index }}.amount" class="mgf-crm-review-scan__input mgf-crm-review-scan__input--cell">
                                    @if (!empty($item['needs_amount']))
                                        <span class="mgf-crm-review-scan__error">Monto requerido</span>
                                    @endif
                                </span>
                            </div>
                            <div class="mgf-crm-data-card__row">
                                <span class="mgf-crm-data-card__label">Confianza</span>
                                <span class="mgf-crm-data-card__value">{{ number_format((float) ($item['confidence'] ?? 0) * 100, 0) }}%</span>
                            </div>
                            <div class="mgf-crm-review-scan__card-actions">
                                <button type="button" wire:click="removeItem({{ $index }})" class="mgf-crm-review-scan__remove">Quitar ítem</button>
                            </div>
                        </article>
                    @empty
                        <p class="mgf-crm-data-card__empty">No se detectaron ítems. Descarta y vuelve a escanear.</p>
                    @endforelse
                </div>
            </div>
        </x-crm.panel>

        <div class="mgf-crm-review-scan__footer">
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:var(--mgf-crm-heading);">
                <input type="checkbox" wire:model="sync_templates">
                Guardar conceptos nuevos en frecuentes
            </label>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <button
                    type="button"
                    wire:click="createDraft"
                    wire:loading.attr="disabled"
                    class="mgf-crm-review-scan__submit"
                    @disabled($this->hasBlockingIssues || count($items) === 0)
                >
                    Crear borrador
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
