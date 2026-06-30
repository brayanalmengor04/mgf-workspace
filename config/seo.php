<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Valores por defecto del sitio
    |--------------------------------------------------------------------------
    |
    | Se usan cuando una vista no define metadatos propios. Sobrescribe en
    | .env o pasa un array a App\Support\Seo::make() por página.
    |
    */

    'site_name' => env('SEO_SITE_NAME', env('APP_BRAND', 'MGF Workspace')),

    'title' => env('SEO_TITLE', 'Seguimiento Financiero Personal | '.env('APP_BRAND', 'MGF Workspace')),

    'description' => env('SEO_DESCRIPTION', 'Plataforma de seguimiento financiero personal. Controla presupuestos, cotizaciones y tus finanzas con una herramienta flexible para uso personal y comercial.'),

    'keywords' => env('SEO_KEYWORDS', 'seguimiento financiero, finanzas personales, presupuestos, cotizaciones, gestión financiera'),

    'image' => env('SEO_IMAGE', 'assets/graphs/web/opengraphs.png'),

    'twitter_card' => env('SEO_TWITTER_CARD', 'summary_large_image'),

    'og_type' => env('SEO_OG_TYPE', 'website'),

    'robots_index' => env('SEO_ROBOTS_INDEX', true),

    'robots_follow' => env('SEO_ROBOTS_FOLLOW', true),

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    |
    | Rutas públicas indexables. La clave es el path (sin dominio).
    |
    */

    'sitemap' => [
        'urls' => [
            '/' => [
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
        ],
    ],

];
