const MOBILE_QUERY = '(max-width: 768px)';

function isMobile() {
    return window.matchMedia(MOBILE_QUERY).matches;
}

function buildToggle(view) {
    const wrap = document.createElement('div');
    wrap.className = 'mgf-crm-data-view__toggle mgf-crm-data-view__toggle--filament';
    wrap.setAttribute('role', 'tablist');
    wrap.setAttribute('aria-label', 'Formato de vista');

    wrap.innerHTML = `
        <button type="button" role="tab" class="mgf-crm-data-view__toggle-btn${view === 'table' ? ' is-active' : ''}" data-view="table" aria-selected="${view === 'table'}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            Tabla
        </button>
        <button type="button" role="tab" class="mgf-crm-data-view__toggle-btn${view === 'cards' ? ' is-active' : ''}" data-view="cards" aria-selected="${view === 'cards'}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>
            Tarjetas
        </button>
    `;

    return wrap;
}

function labelTableCells(table) {
    const headers = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

    if (!headers.length) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        [...row.querySelectorAll('td')].forEach((cell, index) => {
            if (headers[index]) {
                cell.setAttribute('data-label', headers[index]);
            }
        });
    });
}

function setFilamentView(container, view) {
    container.classList.toggle('mgf-filament-table--cards', view === 'cards');
    container.classList.toggle('mgf-filament-table--table', view === 'table');

    container.querySelectorAll('.mgf-crm-data-view__toggle-btn').forEach((button) => {
        const active = button.dataset.view === view;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
}

function enhanceFilamentTable(container) {
    const table = container.querySelector('table.fi-ta-table');
    if (!table || !table.tHead) {
        return;
    }

    labelTableCells(table);

    if (container.dataset.mgfTableView) {
        return;
    }

    const defaultView = isMobile() ? 'cards' : 'table';
    const toggle = buildToggle(defaultView);

    toggle.addEventListener('click', (event) => {
        const button = event.target.closest('[data-view]');
        if (!button) {
            return;
        }

        setFilamentView(container, button.dataset.view);
    });

    const header = container.querySelector('.fi-ta-header-ctn') ?? container.querySelector('.fi-ta-header');
    if (header) {
        header.appendChild(toggle);
    } else {
        container.prepend(toggle);
    }

    setFilamentView(container, defaultView);
    container.dataset.mgfTableView = '1';
}

export function initCrmTableViews() {
    document.querySelectorAll('.fi-ta-ctn').forEach(enhanceFilamentTable);
}

let morphTimer;

function scheduleInit() {
    window.clearTimeout(morphTimer);
    morphTimer = window.setTimeout(initCrmTableViews, 80);
}

document.addEventListener('DOMContentLoaded', initCrmTableViews);
document.addEventListener('livewire:navigated', initCrmTableViews);

document.addEventListener('livewire:initialized', () => {
    if (!window.Livewire?.hook) {
        return;
    }

    window.Livewire.hook('morph.updated', scheduleInit);
});
