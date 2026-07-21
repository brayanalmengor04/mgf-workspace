import { copyFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'resources', 'favicon');
const brandDir = join(root, 'public', 'images', 'brand');
const publicDir = join(root, 'public');

mkdirSync(outDir, { recursive: true });
mkdirSync(brandDir, { recursive: true });

const sharp = (await import('sharp')).default;

/**
 * MGF mark — dark slate tile + amber growth chart monogram.
 * Reads clearly from favicon (16px) to PWA (512px).
 */
function appIconSvg(size, { maskable = false } = {}) {
    const safe = maskable ? 0.2 : 0.0;
    const inset = size * safe;
    const tile = size - inset * 2;
    const rx = tile * (maskable ? 0 : 0.22);
    const cx = inset + tile / 2;
    const cy = inset + tile / 2;

    // Chart bars (ascending) — finance / growth metaphor forming an abstract "M"
    const barW = tile * 0.12;
    const gap = tile * 0.055;
    const baseY = cy + tile * 0.22;
    const heights = [tile * 0.28, tile * 0.42, tile * 0.58];
    const totalW = barW * 3 + gap * 2;
    const startX = cx - totalW / 2;

    const bars = heights
        .map((h, i) => {
            const x = startX + i * (barW + gap);
            const y = baseY - h;
            const r = Math.max(2, barW * 0.28);

            return `<rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${barW.toFixed(2)}" height="${h.toFixed(2)}" rx="${r.toFixed(2)}" fill="url(#amberGrad)"/>`;
        })
        .join('');

    // Peak connector — soft "M" silhouette over the bars
    const p0x = startX + barW * 0.5;
    const p0y = baseY - heights[0];
    const p1x = startX + barW + gap + barW * 0.5;
    const p1y = baseY - heights[1] - tile * 0.04;
    const p2x = startX + 2 * (barW + gap) + barW * 0.5;
    const p2y = baseY - heights[2];
    const stroke = Math.max(2, tile * 0.045);

    const peak = `
        <path d="M ${p0x.toFixed(2)} ${p0y.toFixed(2)}
                 L ${p1x.toFixed(2)} ${p1y.toFixed(2)}
                 L ${p2x.toFixed(2)} ${p2y.toFixed(2)}"
              fill="none" stroke="#fffbeb" stroke-width="${stroke.toFixed(2)}"
              stroke-linecap="round" stroke-linejoin="round" opacity="0.95"/>
        <circle cx="${p2x.toFixed(2)}" cy="${p2y.toFixed(2)}" r="${(stroke * 0.85).toFixed(2)}" fill="#fffbeb"/>
    `;

    // Tiny baseline label for mid/large icons only
    const label =
        size >= 96
            ? `<text x="${cx}" y="${(inset + tile * 0.9).toFixed(2)}" text-anchor="middle"
                font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
                font-weight="800" font-size="${(tile * 0.11).toFixed(2)}"
                letter-spacing="${(tile * 0.04).toFixed(2)}" fill="#fde68a">MGF</text>`
            : '';

    const glowR = tile * 0.55;

    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="amberGrad" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="62%" r="55%">
      <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.35"/>
      <stop offset="70%" stop-color="#f59e0b" stop-opacity="0.08"/>
      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="${size}" height="${size}" fill="#0f172a"/>
  <rect x="${inset}" y="${inset}" width="${tile}" height="${tile}" rx="${rx}" fill="#0f172a"/>
  <circle cx="${cx}" cy="${(cy + tile * 0.08).toFixed(2)}" r="${glowR.toFixed(2)}" fill="url(#glow)"/>
  ${bars}
  ${peak}
  ${label}
</svg>`;
}

/** Horizontal lockup for Filament sidebar / offline page */
function brandLogoSvg() {
    return `<svg width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MGF Workspace">
  <defs>
    <linearGradient id="g" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="48" height="48" rx="12" fill="#0f172a"/>
  <rect x="14" y="24" width="5.5" height="12" rx="1.6" fill="url(#g)"/>
  <rect x="21.5" y="18" width="5.5" height="18" rx="1.6" fill="url(#g)"/>
  <rect x="29" y="12" width="5.5" height="24" rx="1.6" fill="url(#g)"/>
  <path d="M16.7 24 L24.2 15.5 L34.5 12" fill="none" stroke="#fffbeb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="34.5" cy="12" r="2" fill="#fffbeb"/>
  <text x="60" y="22" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="16" letter-spacing="0.04em" fill="#0f172a">MGF</text>
  <text x="60" y="38" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="600" font-size="11" letter-spacing="0.12em" fill="#92400e">WORKSPACE</text>
</svg>`;
}

/** Dark variant for Filament dark mode */
function brandLogoDarkSvg() {
    return `<svg width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MGF Workspace">
  <defs>
    <linearGradient id="gd" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="48" height="48" rx="12" fill="#0f172a"/>
  <rect x="14" y="24" width="5.5" height="12" rx="1.6" fill="url(#gd)"/>
  <rect x="21.5" y="18" width="5.5" height="18" rx="1.6" fill="url(#gd)"/>
  <rect x="29" y="12" width="5.5" height="24" rx="1.6" fill="url(#gd)"/>
  <path d="M16.7 24 L24.2 15.5 L34.5 12" fill="none" stroke="#fffbeb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="34.5" cy="12" r="2" fill="#fffbeb"/>
  <text x="60" y="22" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="16" letter-spacing="0.04em" fill="#fffbeb">MGF</text>
  <text x="60" y="38" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="600" font-size="11" letter-spacing="0.12em" fill="#fbbf24">WORKSPACE</text>
</svg>`;
}

async function createIcon(size, filename, maskable = false) {
    const svg = appIconSvg(size, { maskable });
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

// Proper multi-resolution favicon.ico (16 + 32)
await sharp(join(outDir, 'favicon-32x32.png'))
    .resize(32, 32)
    .toFile(join(outDir, 'favicon.ico'));

writeFileSync(join(brandDir, 'mgf-logo.svg'), brandLogoSvg());
writeFileSync(join(brandDir, 'mgf-logo-dark.svg'), brandLogoDarkSvg());
writeFileSync(join(brandDir, 'mgf-mark.svg'), appIconSvg(128, { maskable: false }));

copyFileSync(join(outDir, 'favicon.ico'), join(publicDir, 'favicon.ico'));
copyFileSync(join(outDir, 'favicon-32x32.png'), join(publicDir, 'favicon-32x32.png'));
copyFileSync(join(outDir, 'apple-icon-180x180.png'), join(publicDir, 'apple-touch-icon.png'));
copyFileSync(join(outDir, 'icon-512x512.png'), join(brandDir, 'mgf-icon-512.png'));

console.log('PWA icons + brand logos generated.');
