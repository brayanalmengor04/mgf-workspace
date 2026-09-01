import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

const faviconAssets = readdirSync(join(process.cwd(), 'resources/favicon'))
    .filter((file) => /\.(png|ico)$/i.test(file))
    .map((file) => `resources/favicon/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/js/crm-echarts.js',
                'resources/js/crm-table-view.js',
                'resources/js/budget-scan-pen-hero.js',
                'resources/js/app.js',
                'resources/js/pwa-install.js',
                ...faviconAssets,
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
});
