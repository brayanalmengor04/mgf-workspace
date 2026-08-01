<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <x-seo-meta :seo="$seo ?? null" />

        @if (config('pwa-favicon.enabled'))
            @include('pwa-favicon::head', [
                'themeColor' => config('pwa-favicon.manifest.theme_color'),
                'title' => config('app.brand'),
            ])
        @else
            <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
            <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
            <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        @endif

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @stack('head')
    </head>
    <body>
        @yield('content')

        <x-pwa-service-worker-register />

        @livewire('chatbot-widget')

        @stack('scripts')
    </body>
</html>
