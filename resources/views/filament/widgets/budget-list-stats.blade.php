@php
    $stats = $stats ?? [];
@endphp

<div class="mgf-crm mgf-crm-budget-list-stats">
    <div class="mgf-crm-stat-section">
        <div class="mgf-crm-stat-section__header">
            <h2 class="mgf-crm-stat-section__title">Resumen de presupuestos</h2>
            <p class="mgf-crm-stat-section__subtitle">Estado general de tus comprobantes y pagos pendientes</p>
        </div>
        <div class="mgf-crm-grid mgf-crm-grid--stats">
            <div class="mgf-crm-stat mgf-crm-stat--accent">
                <div class="mgf-crm-stat__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="mgf-crm-stat__label">Emitidos</div>
                <div class="mgf-crm-stat__value">{{ $stats['issued'] ?? 0 }}</div>
                <div class="mgf-crm-stat__hint">Comprobantes activos</div>
            </div>

            <div class="mgf-crm-stat">
                <div class="mgf-crm-stat__icon mgf-crm-stat__icon--muted" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div class="mgf-crm-stat__label">Borradores</div>
                <div class="mgf-crm-stat__value">{{ $stats['drafts'] ?? 0 }}</div>
                <div class="mgf-crm-stat__hint">Por emitir o revisar</div>
            </div>

            <div class="mgf-crm-stat mgf-crm-stat--warning">
                <div class="mgf-crm-stat__icon mgf-crm-stat__icon--warning" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="mgf-crm-stat__label">Pendiente por pagar</div>
                <div class="mgf-crm-stat__value mgf-crm-stat__value--warning">{{ $stats['pending_items_formatted'] ?? '—' }}</div>
                <div class="mgf-crm-stat__hint">{{ $stats['pending_items_count'] ?? 0 }} ítems sin marcar pagados</div>
            </div>

            <div class="mgf-crm-stat">
                <div class="mgf-crm-stat__icon mgf-crm-stat__icon--muted" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div class="mgf-crm-stat__label">Total registrados</div>
                <div class="mgf-crm-stat__value">{{ $stats['total'] ?? 0 }}</div>
                <div class="mgf-crm-stat__hint">Incluye archivados</div>
            </div>
        </div>
    </div>
</div>
