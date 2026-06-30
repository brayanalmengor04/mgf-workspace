# SEO del sitio web

Guía de metadatos, Open Graph y sitemap para las páginas públicas de MGF Workspace.

## Resumen

| Qué | Dónde |
|-----|-------|
| Variables de entorno | `.env` → claves `SEO_*` |
| Configuración central | `config/seo.php` |
| Objeto de metadatos | `app/Support/Seo.php` |
| Etiquetas `<meta>` renderizadas | `resources/views/components/seo-meta.blade.php` |
| Layout público con SEO | `resources/views/layouts/public.blade.php` |
| Sitemap XML | ruta `/sitemap.xml` → `app/Http/Controllers/SeoController.php` |
| Plantilla del sitemap | `resources/views/seo/sitemap.blade.php` |
| Reglas para crawlers | `public/robots.txt` |
| Panel admin (no indexar) | `app/Providers/Filament/AdminPanelProvider.php` |

## Variables de entorno

Copia estas claves en tu `.env` (ya están en `.env.example`):

```env
SEO_SITE_NAME="${APP_NAME}"
SEO_TITLE="Seguimiento Financiero Personal | ${APP_NAME}"
SEO_DESCRIPTION="Plataforma de seguimiento financiero personal. Controla presupuestos, cotizaciones y tus finanzas con una herramienta flexible para uso personal y comercial."
SEO_KEYWORDS="seguimiento financiero, finanzas personales, presupuestos, cotizaciones, gestión financiera"
SEO_IMAGE=assets/graphs/web/opengraphs.png
SEO_TWITTER_CARD=summary_large_image
SEO_OG_TYPE=website
SEO_ROBOTS_INDEX=true
SEO_ROBOTS_FOLLOW=true
```

| Variable | Uso |
|----------|-----|
| `SEO_SITE_NAME` | Nombre del sitio en `og:site_name` |
| `SEO_TITLE` | `<title>` por defecto |
| `SEO_DESCRIPTION` | Meta description y Open Graph |
| `SEO_KEYWORDS` | Meta keywords (opcional) |
| `SEO_IMAGE` | Imagen OG/Twitter (ruta en `public/` o URL absoluta) |
| `SEO_TWITTER_CARD` | `summary`, `summary_large_image`, etc. |
| `SEO_OG_TYPE` | Tipo Open Graph (`website`, `article`, …) |
| `SEO_ROBOTS_INDEX` | `true` → `index`; `false` → `noindex` |
| `SEO_ROBOTS_FOLLOW` | `true` → `follow`; `false` → `nofollow` |

Tras cambiar `.env` en producción:

```bash
just artisan config:clear
```

## Metadatos que se generan

El componente `<x-seo-meta />` incluye:

- `<title>`
- `meta name="description"`
- `meta name="keywords"` (si hay valor)
- `meta name="robots"`
- `link rel="canonical"`
- Open Graph: `og:title`, `og:description`, `og:type`, `og:url`, `og:site_name`, `og:locale`, `og:image`
- Twitter Card: `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`

## Uso en una vista pública

### Opción A — Layout público (recomendado)

```blade
{{-- resources/views/mi-pagina.blade.php --}}
@extends('layouts.public')

@section('content')
    <main>
        <h1>Mi página</h1>
    </main>
@endsection
```

En la ruta, pasa metadatos personalizados:

```php
use App\Support\Seo;
use Illuminate\Support\Facades\Route;

Route::get('/mi-pagina', fn () => view('mi-pagina', [
    'seo' => Seo::make([
        'title' => 'Mi página | '.config('app.name'),
        'description' => 'Descripción específica de esta página.',
        'canonical' => url('/mi-pagina'),
    ]),
]));
```

### Opción B — Componente suelto

Si ya tienes un layout propio, incluye solo el componente en el `<head>`:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-meta :seo="$seo ?? null" />
</head>
```

Sin `$seo`, se usan los valores de `config/seo.php`.

### Opción C — Array inline

```blade
<x-seo-meta :seo="[
    'title' => 'Contacto',
    'description' => 'Escríbenos para una cotización.',
]" />
```

## Imagen Open Graph

La imagen por defecto está en `public/assets/graphs/web/opengraphs.png` (recomendado **1200×630 px**). La misma ruta se usa para Open Graph (Facebook, LinkedIn, WhatsApp) y Twitter Card (`twitter:image`).

Puedes cambiar la ruta con `SEO_IMAGE` o por página:

```php
Seo::make(['image' => 'assets/graphs/web/opengraphs.png'])
```

URLs absolutas también funcionan:

```php
Seo::make(['image' => 'https://tudominio.com/img/og.png'])
```

## Sitemap

Registra URLs públicas en `config/seo.php`:

```php
'sitemap' => [
    'urls' => [
        '/mi-pagina' => [
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ],
    ],
],
```

El sitemap se sirve en: **https://tu-dominio/sitemap.xml**

`public/robots.txt` ya apunta a `/sitemap.xml` y bloquea `/admin` para que los buscadores no indexen el panel Filament.

## Panel administrativo

Las rutas bajo `/admin` llevan `noindex, nofollow` inyectado en el `<head>` desde `AdminPanelProvider`. No dependen de las variables `SEO_*`.

## Checklist al publicar

1. Definir `APP_URL` con el dominio real (HTTPS).
2. Ajustar `SEO_TITLE`, `SEO_DESCRIPTION` y `SEO_IMAGE`.
3. Añadir páginas públicas al array `seo.sitemap.urls`.
4. Verificar `/sitemap.xml` y `/robots.txt`.
5. Probar metadatos con [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) o similar.

## Ejemplo de flujo

```
.env (SEO_*)
    ↓
config/seo.php
    ↓
App\Support\Seo::make([...])   ← overrides por página
    ↓
<x-seo-meta />                 ← HTML en <head>
    ↓
Navegador / redes sociales / buscadores
```
