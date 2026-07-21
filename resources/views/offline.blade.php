<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0f172a">
        <link rel="icon" href="{{ asset('favicon.ico') }}">
        <title>Sin conexión — {{ config('app.brand') }}</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                color: #fffbeb;
                background:
                    radial-gradient(ellipse 80% 60% at 50% -10%, rgba(245, 158, 11, 0.35), transparent 55%),
                    radial-gradient(circle at 85% 90%, rgba(217, 119, 6, 0.2), transparent 40%),
                    #0f172a;
            }
            .card {
                max-width: 28rem;
                width: 100%;
                text-align: center;
                background: rgba(15, 23, 42, 0.72);
                border: 1px solid rgba(251, 191, 36, 0.22);
                border-radius: 1.25rem;
                padding: 2.25rem 1.75rem;
                backdrop-filter: blur(12px);
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
            }
            .mark {
                width: 4.5rem;
                height: 4.5rem;
                margin: 0 auto 1.25rem;
                border-radius: 1rem;
                display: block;
                box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.25), 0 12px 28px rgba(245, 158, 11, 0.2);
            }
            .badge {
                display: inline-block;
                margin-bottom: 1rem;
                padding: 0.35rem 0.85rem;
                border-radius: 9999px;
                background: rgba(245, 158, 11, 0.16);
                color: #fbbf24;
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }
            h1 {
                margin: 0 0 0.75rem;
                font-size: 1.55rem;
                line-height: 1.2;
                letter-spacing: -0.02em;
            }
            p {
                margin: 0 0 1.5rem;
                color: #cbd5e1;
                line-height: 1.65;
            }
            button {
                appearance: none;
                border: 0;
                border-radius: 0.85rem;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: #0f172a;
                font-weight: 800;
                font-size: 0.95rem;
                padding: 0.85rem 1.35rem;
                cursor: pointer;
                box-shadow: 0 10px 24px rgba(245, 158, 11, 0.28);
            }
            button:hover { filter: brightness(1.06); }
        </style>
    </head>
    <body>
        <div class="card">
            <img class="mark" src="{{ asset('images/brand/mgf-mark.svg') }}" width="72" height="72" alt="{{ config('app.brand') }}">
            <div class="badge">{{ config('app.brand') }}</div>
            <h1>Sin conexión a internet</h1>
            <p>
                El panel administrativo necesita conexión para iniciar sesión y gestionar cotizaciones.
                Cuando vuelvas a estar en línea, recarga la página para continuar.
            </p>
            <button type="button" onclick="window.location.reload()">Reintentar</button>
        </div>
    </body>
</html>
