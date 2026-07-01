@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/pwa-install.js')
@endif

<div class="mt-4 flex justify-center">
    <x-pwa-install-guide />
</div>
