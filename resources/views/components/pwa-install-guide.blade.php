@props([
    'panelUrl' => null,
    'buttonClass' => '',
])

@php
    $panelUrl ??= url('/admin/login?install=1');
@endphp

<div data-pwa-install-root {{ $attributes->class(['sm:w-auto']) }}>
    <button
        type="button"
        data-pwa-install
        data-pwa-panel-url="{{ $panelUrl }}"
        @class([
            'flex w-full items-center justify-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-8 py-3.5 text-sm font-semibold text-amber-300 backdrop-blur-md transition-all hover:border-amber-400 hover:bg-amber-500/20 hover:text-amber-200 sm:w-auto',
            $buttonClass,
        ])
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        Instalar app
    </button>
</div>
