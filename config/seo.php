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

    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'Laravel')),

    'title' => env('SEO_TITLE', env('APP_NAME', 'Laravel')),

    'description' => env('SEO_DESCRIPTION', 'Sistema de cotizaciones y presupuestos para tu negocio.'),

    'keywords' => env('SEO_KEYWORDS'),

    'image' => env('SEO_IMAGE', 'images/og-default.png'),

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
            // '/' => [
            //     'changefreq' => 'weekly',
            //     'priority' => '1.0',
            // ],
        ],
    ],

];
