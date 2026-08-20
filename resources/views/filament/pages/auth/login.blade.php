@php
    $brand = (string) config('app.brand');
    $description = (string) config('seo.description', 'Presupuestos, ahorros y cotizaciones en un solo lugar.');
@endphp

<div class="mgf-auth-shell">
    <aside class="mgf-auth-hero mgf-auth-hero--desktop-only">
        <div class="mgf-auth-grid"></div>

        <div class="mgf-auth-hero-inner">
            <div class="mgf-auth-mark">
                <img src="{{ asset('images/brand/mgf-mark.svg') }}" alt="{{ $brand }}">
                <span class="mgf-auth-brand-pill">{{ $brand }}</span>
            </div>

            <h1>
                Tu control
                <span>financiero</span>
                en un solo panel
            </h1>

            <p>{{ $description }}</p>
        </div>
    </aside>

    <main class="mgf-auth-panel">
        <div class="mgf-auth-card">
            <div class="mgf-auth-card-header">
                <img
                    src="{{ asset('images/brand/mgf-mark.svg') }}"
                    alt=""
                    class="mgf-auth-card-logo"
                    width="44"
                    height="44"
                >
                <h2>Bienvenido de nuevo</h2>
                <p>Ingresa a {{ $brand }}</p>
            </div>

            {{ $this->content }}
        </div>
    </main>
</div>
