import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'resources', 'favicon');
const sourceIco = join(root, 'public', 'favicon.ico');

mkdirSync(outDir, { recursive: true });

if (existsSync(sourceIco) && (await import('node:fs')).statSync(sourceIco).size > 0) {
    copyFileSync(sourceIco, join(outDir, 'favicon.ico'));
}

const sharp = (await import('sharp')).default;

const background = { r: 245, g: 158, b: 11, alpha: 1 };
const foreground = '#ffffff';

async function createIcon(size, filename, maskable = false) {
    const padding = maskable ? Math.round(size * 0.1) : 0;
    const inner = size - padding * 2;
    const fontSize = Math.round(inner * 0.32);

    const svg = maskable
        ? `<svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
            <rect width="${size}" height="${size}" fill="rgb(${background.r},${background.g},${background.b})"/>
            <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle"
                font-family="Arial, sans-serif" font-weight="700" font-size="${fontSize}" fill="${foreground}">MGF</text>
           </svg>`
        : `<svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
            <rect width="${size}" height="${size}" rx="${Math.round(size * 0.18)}" fill="rgb(${background.r},${background.g},${background.b})"/>
            <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle"
                font-family="Arial, sans-serif" font-weight="700" font-size="${fontSize}" fill="${foreground}">MGF</text>
           </svg>`;

    await sharp(Buffer.from(svg)).png().toFile(join(outDir, filename));
}

const sizes = {
    'android-icon-36x36.png': 36,
    'android-icon-48x48.png': 48,
    'android-icon-72x72.png': 72,
    'android-icon-96x96.png': 96,
    'android-icon-144x144.png': 144,
    'android-icon-192x192.png': 192,
    'icon-512x512.png': 512,
    'icon-512x512-maskable.png': 512,
    'apple-icon-57x57.png': 57,
    'apple-icon-60x60.png': 60,
    'apple-icon-72x72.png': 72,
    'apple-icon-76x76.png': 76,
    'apple-icon-114x114.png': 114,
    'apple-icon-120x120.png': 120,
    'apple-icon-144x144.png': 144,
    'apple-icon-152x152.png': 152,
    'apple-icon-180x180.png': 180,
    'ms-icon-70x70.png': 70,
    'ms-icon-144x144.png': 144,
    'ms-icon-150x150.png': 150,
    'ms-icon-310x310.png': 310,
    'favicon-16x16.png': 16,
    'favicon-32x32.png': 32,
    'favicon-96x96.png': 96,
};

for (const [filename, size] of Object.entries(sizes)) {
    await createIcon(size, filename, filename.includes('maskable'));
}

if (! existsSync(join(outDir, 'favicon.ico')) || (await import('node:fs')).statSync(join(outDir, 'favicon.ico')).size === 0) {
    await sharp(join(outDir, 'favicon-32x32.png')).toFile(join(outDir, 'favicon.ico'));
}

const publicDir = join(root, 'public');

copyFileSync(join(outDir, 'favicon.ico'), join(publicDir, 'favicon.ico'));
copyFileSync(join(outDir, 'favicon-32x32.png'), join(publicDir, 'favicon-32x32.png'));
copyFileSync(join(outDir, 'apple-icon-180x180.png'), join(publicDir, 'apple-touch-icon.png'));

console.log('PWA icons generated in resources/favicon/ and copied to public/');
