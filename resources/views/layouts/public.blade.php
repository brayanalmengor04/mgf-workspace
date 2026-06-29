<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <x-seo-meta :seo="$seo ?? null" />

        @fonts
        @stack('head')
    </head>
    <body>
        @yield('content')
        @stack('scripts')
    </body>
</html>
