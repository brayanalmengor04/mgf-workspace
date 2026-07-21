<?php

return [

    'enabled' => env('PWA_FAVICON_ENABLED', true),

    'manifest' => [
        'id' => '/admin',
        'name' => config('app.brand'),
        'short_name' => 'MGF',
        'description' => 'Cotizaciones, presupuestos y finanzas personales en un solo panel.',
        'start_url' => '/admin?source=pwa',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'theme_color' => '#0f172a',
        'background_color' => '#0f172a',
        'lang' => 'es',
        'dir' => 'ltr',
        'categories' => ['business', 'productivity', 'finance'],
        'icons' => [
            '36' => '0.75',
            '48' => '1.0',
            '72' => '1.5',
            '96' => '2.0',
            '144' => '3.0',
            '192' => '4.0',
        ],
    ],

    'favicon' => 'resources/favicon/favicon.ico',

    'browserconfig_url' => '/browserconfig.xml',

    'tile_color' => '#0f172a',

    'apple_status_bar_style' => 'black-translucent',
];
