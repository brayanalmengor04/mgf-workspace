<?php

return [

    'enabled' => env('PWA_SW_ENABLED', true),

    'path' => env('PWA_SW_PATH', 'sw.js'),

    'middleware' => [],

    'view' => 'pwa-service-worker::sw',

    'build_manifest' => public_path('build/manifest.json'),

    'cache_prefix' => env('PWA_SW_CACHE_PREFIX', 'pwa'),

    'offline_url' => env('PWA_SW_OFFLINE_URL', '/offline'),

    'precache_urls' => ['/offline', '/admin/login'],

    'passthrough_prefixes' => ['/admin', '/livewire', '/api', '/horizon', '/telescope', '/pulse'],

    'passthrough_exact' => ['/sw.js', '/manifest.json'],

    'asset_prefix' => '/build/',
];
