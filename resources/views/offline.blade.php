<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f59e0b">
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
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: #fffbeb;
                color: #1c1917;
            }
            .card {
                max-width: 28rem;
                width: 100%;
                text-align: center;
                background: #ffffff;
                border: 1px solid #fde68a;
                border-radius: 1rem;
                padding: 2rem 1.5rem;
                box-shadow: 0 10px 30px rgba(245, 158, 11, 0.12);
            }
            .badge {
                display: inline-block;
                margin-bottom: 1rem;
                padding: 0.35rem 0.85rem;
                border-radius: 9999px;
                background: rgba(245, 158, 11, 0.12);
                color: #b45309;
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            h1 {
                margin: 0 0 0.75rem;
                font-size: 1.5rem;
                line-height: 1.2;
            }
            p {
                margin: 0 0 1.5rem;
                color: #57534e;
                line-height: 1.6;
            }
            button {
                appearance: none;
                border: 0;
                border-radius: 0.75rem;
                background: #f59e0b;
                color: #1c1917;
                font-weight: 700;
                font-size: 0.95rem;
                padding: 0.8rem 1.25rem;
                cursor: pointer;
            }
            button:hover { background: #fbbf24; }
        </style>
    </head>
    <body>
        <div class="card">
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
