<div class="mgf-crm-data-view__toggle" role="tablist" aria-label="Formato de vista">
    <button
        type="button"
        role="tab"
        class="mgf-crm-data-view__toggle-btn"
        :class="{ 'is-active': view === 'table' }"
        :aria-selected="view === 'table'"
        @click="view = 'table'"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        Tabla
    </button>
    <button
        type="button"
        role="tab"
        class="mgf-crm-data-view__toggle-btn"
        :class="{ 'is-active': view === 'cards' }"
        :aria-selected="view === 'cards'"
        @click="view = 'cards'"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="3" width="8" height="8" rx="1"/>
            <rect x="13" y="3" width="8" height="8" rx="1"/>
            <rect x="3" y="13" width="8" height="8" rx="1"/>
            <rect x="13" y="13" width="8" height="8" rx="1"/>
        </svg>
        Tarjetas
    </button>
</div>
