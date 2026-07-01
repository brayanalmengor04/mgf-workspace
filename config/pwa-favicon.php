<?php

return [

    'enabled' => env('PWA_FAVICON_ENABLED', true),

    'manifest' => [
        'id' => '/admin',
        'name' => config('app.brand'),
        'short_name' => config('app.brand'),
        'description' => 'Panel de cotizaciones y presupuestos.',
        'start_url' => '/admin?source=pwa',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'theme_color' => '#f59e0b',
        'background_color' => '#fffbeb',
        'lang' => 'es',
        'dir' => 'ltr',
        'categories' => ['business', 'productivity'],
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

    'tile_color' => '#f59e0b',

    'apple_status_bar_style' => 'black-translucent',
];
