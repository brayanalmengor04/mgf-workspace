<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(config('seo.sitemap.urls', []))
            ->map(fn (array $entry, string $path): array => [
                'loc' => url($path),
                'lastmod' => $entry['lastmod'] ?? now()->toAtomString(),
                'changefreq' => $entry['changefreq'] ?? 'monthly',
                'priority' => $entry['priority'] ?? '0.5',
            ])
            ->values();

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
