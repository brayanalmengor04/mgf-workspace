<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_offline_fallback_page_is_available(): void
    {
        $response = $this->get(route('pwa.offline'));

        $response->assertOk();
        $response->assertSee((string) config('app.brand'), false);
        $response->assertSee('Sin conexión a internet', false);
    }

    public function test_pwa_manifest_opens_admin_from_site_scope(): void
    {
        $manifest = config('pwa-favicon.manifest');

        $this->assertSame('/admin?source=pwa', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#f59e0b', $manifest['theme_color']);
    }

    public function test_service_worker_keeps_admin_and_livewire_online_first(): void
    {
        $passthrough = config('pwa-service-worker.passthrough_prefixes');

        $this->assertContains('/admin', $passthrough);
        $this->assertContains('/livewire', $passthrough);
        $this->assertSame('/offline', config('pwa-service-worker.offline_url'));
    }

    public function test_required_pwa_icon_assets_exist(): void
    {
        $required = [
            'resources/favicon/android-icon-192x192.png',
            'resources/favicon/icon-512x512.png',
            'resources/favicon/icon-512x512-maskable.png',
            'public/favicon.ico',
        ];

        foreach ($required as $path) {
            $this->assertFileExists(base_path($path), "Missing PWA icon: {$path}");
            $this->assertGreaterThan(0, filesize(base_path($path)), "Empty PWA icon: {$path}");
        }
    }

    public function test_home_page_includes_favicon_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('rel="icon"', false);
    }
}
