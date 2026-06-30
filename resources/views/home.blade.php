@extends('layouts.public')

@push('head')
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @endif
    <style>
        .home-shell {
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr;
            background: #0a0a0a;
            color: #f5f5f4;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        @media (min-width: 1024px) {
            .home-shell {
                grid-template-columns: 1fr 1fr;
            }
        }

        .home-hero {
            position: relative;
            min-height: 18rem;
            background:
                linear-gradient(180deg, rgba(10, 10, 10, 0.25) 0%, rgba(10, 10, 10, 0.85) 100%),
                url('{{ asset('assets/graphs/web/opengraphs.png') }}') center / cover no-repeat;
        }

        @media (min-width: 1024px) {
            .home-hero {
                min-height: 100dvh;
            }
        }

        .home-hero__content {
            position: absolute;
            inset: auto 0 0 0;
            padding: 2rem;
        }

        .home-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        .home-card {
            width: 100%;
            max-width: 32rem;
        }

        .home-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            border: 1px solid rgba(251, 191, 36, 0.35);
            background: rgba(251, 191, 36, 0.12);
            color: #fbbf24;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            padding: 0.35rem 0.75rem;
            text-transform: uppercase;
        }

        .home-title {
            margin-top: 1rem;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.1;
            font-weight: 700;
        }

        .home-copy {
            margin-top: 1rem;
            color: #a8a29e;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .home-actions {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            padding: 0.8rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .home-btn:hover {
            transform: translateY(-1px);
        }

        .home-btn--primary {
            background: #f59e0b;
            color: #1c1917;
        }

        .home-btn--secondary {
            border: 1px solid #44403c;
            color: #e7e5e4;
        }

        .home-features {
            margin-top: 2rem;
            display: grid;
            gap: 0.75rem;
        }

        .home-feature {
            border: 1px solid #292524;
            border-radius: 0.75rem;
            padding: 0.9rem 1rem;
            background: rgba(28, 25, 23, 0.65);
        }

        .home-feature strong {
            display: block;
            color: #fafaf9;
            margin-bottom: 0.2rem;
        }

        .home-feature span {
            color: #a8a29e;
            font-size: 0.92rem;
        }
    </style>
@endpush

@section('content')
    <div class="home-shell">
        <section class="home-hero" aria-hidden="true">
            <div class="home-hero__content">
                <span class="home-badge">Finanzas personales</span>
                <h1 class="home-title">Sistema de Seguimiento Financiero</h1>
            </div>
        </section>

        <section class="home-panel">
            <div class="home-card">
                <span class="home-badge">{{ config('app.brand') }}</span>
                <h2 class="home-title" style="font-size: clamp(1.75rem, 3vw, 2.25rem);">
                    Controla tus finanzas en un solo lugar
                </h2>
                <p class="home-copy">
                    {{ config('seo.description') }}
                </p>

                <div class="home-actions">
                    <a href="{{ url('/admin/login') }}" class="home-btn home-btn--primary">
                        Iniciar sesión
                    </a>
                    <a href="{{ url('/sitemap.xml') }}" class="home-btn home-btn--secondary">
                        Sitemap
                    </a>
                </div>

                <div class="home-features">
                    <div class="home-feature">
                        <strong>Presupuestos</strong>
                        <span>Planifica y da seguimiento a tus gastos mensuales.</span>
                    </div>
                    <div class="home-feature">
                        <strong>Cotizaciones</strong>
                        <span>Genera y administra cotizaciones con plantillas.</span>
                    </div>
                    <div class="home-feature">
                        <strong>Uso personal y comercial</strong>
                        <span>Herramienta flexible para ti o para tu negocio.</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
