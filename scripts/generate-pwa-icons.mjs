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

const BG = '#0f172a';

/**
 * Growth-chart monogram. Mobile-first:
 * - any: full-bleed slate square (Apple/Android home)
 * - maskable: 24% safe padding, no wordmark (survives circular crop)
 */
function appIconSvg(size, { maskable = false, showWordmark = null } = {}) {
    const safe = maskable ? 0.24 : 0;
    const inset = size * safe;
    const tile = size - inset * 2;
    const cx = inset + tile / 2;
    // Bias mark slightly upward so wordmark (if any) fits below
    const includeWordmark = showWordmark ?? (! maskable && size >= 128);
    const cy = inset + tile * (includeWordmark ? 0.42 : 0.5);

    const barW = tile * (maskable ? 0.14 : 0.13);
    const gap = tile * 0.06;
    const baseY = cy + tile * (includeWordmark ? 0.18 : 0.22);
    const heights = [
        tile * (maskable ? 0.32 : 0.3),
        tile * (maskable ? 0.48 : 0.44),
        tile * (maskable ? 0.64 : 0.58),
    ];
    const totalW = barW * 3 + gap * 2;
    const startX = cx - totalW / 2;

    const bars = heights
        .map((h, i) => {
            const x = startX + i * (barW + gap);
            const y = baseY - h;
            const r = Math.max(2, barW * 0.3);

            return `<rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${barW.toFixed(2)}" height="${h.toFixed(2)}" rx="${r.toFixed(2)}" fill="url(#amberGrad)"/>`;
        })
        .join('');

    const p0x = startX + barW * 0.5;
    const p0y = baseY - heights[0];
    const p1x = startX + barW + gap + barW * 0.5;
    const p1y = baseY - heights[1] - tile * 0.05;
    const p2x = startX + 2 * (barW + gap) + barW * 0.5;
    const p2y = baseY - heights[2];
    const stroke = Math.max(2.5, tile * 0.05);

    const peak = `
        <path d="M ${p0x.toFixed(2)} ${p0y.toFixed(2)}
                 L ${p1x.toFixed(2)} ${p1y.toFixed(2)}
                 L ${p2x.toFixed(2)} ${p2y.toFixed(2)}"
              fill="none" stroke="#fffbeb" stroke-width="${stroke.toFixed(2)}"
              stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${p2x.toFixed(2)}" cy="${p2y.toFixed(2)}" r="${(stroke * 0.9).toFixed(2)}" fill="#fffbeb"/>
    `;

    const label = includeWordmark
        ? `<text x="${cx}" y="${(inset + tile * 0.88).toFixed(2)}" text-anchor="middle"
                font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
                font-weight="800" font-size="${(tile * 0.12).toFixed(2)}"
                letter-spacing="${(tile * 0.05).toFixed(2)}" fill="#fde68a">MGF</text>`
        : '';

    const glowR = tile * 0.5;

    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="amberGrad" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="48%" r="52%">
      <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.32"/>
      <stop offset="65%" stop-color="#f59e0b" stop-opacity="0.08"/>
      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="${size}" height="${size}" fill="${BG}"/>
  <circle cx="${cx}" cy="${cy}" r="${glowR.toFixed(2)}" fill="url(#glow)"/>
  ${bars}
  ${peak}
  ${label}
</svg>`;
}

/** Splash: dark canvas + oversized growth mark (fills the viewport). */
function splashSvg(width, height) {
    const chartW = Math.round(Math.min(width * 0.9, height * 0.45));
    const chartH = chartW;
    const cx = width / 2;
    const cy = height / 2 - height * 0.03;

    const barW = chartW * 0.18;
    const gap = chartW * 0.075;
    const baseY = cy + chartH * 0.3;
    const heights = [chartH * 0.42, chartH * 0.62, chartH * 0.82];
    const totalW = barW * 3 + gap * 2;
    const startX = cx - totalW / 2;

    const bars = heights
        .map((h, i) => {
            const x = startX + i * (barW + gap);
            const y = baseY - h;
            const r = Math.max(4, barW * 0.32);

            return `<rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${barW.toFixed(2)}" height="${h.toFixed(2)}" rx="${r.toFixed(2)}" fill="url(#splashGrad)"/>`;
        })
        .join('');

    const p0x = startX + barW * 0.5;
    const p0y = baseY - heights[0];
    const p1x = startX + barW + gap + barW * 0.5;
    const p1y = baseY - heights[1] - chartH * 0.05;
    const p2x = startX + 2 * (barW + gap) + barW * 0.5;
    const p2y = baseY - heights[2];
    const stroke = Math.max(6, chartW * 0.055);

    const labelY = baseY + chartH * 0.18;
    const labelSize = Math.round(chartW * 0.15);

    return `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="splashGrad" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
    <radialGradient id="splashGlow" cx="50%" cy="46%" r="42%">
      <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.4"/>
      <stop offset="60%" stop-color="#f59e0b" stop-opacity="0.1"/>
      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="${width}" height="${height}" fill="${BG}"/>
  <circle cx="${cx}" cy="${cy}" r="${(chartW * 0.62).toFixed(2)}" fill="url(#splashGlow)"/>
  ${bars}
  <path d="M ${p0x.toFixed(2)} ${p0y.toFixed(2)}
           L ${p1x.toFixed(2)} ${p1y.toFixed(2)}
           L ${p2x.toFixed(2)} ${p2y.toFixed(2)}"
        fill="none" stroke="#fffbeb" stroke-width="${stroke.toFixed(2)}"
        stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="${p2x.toFixed(2)}" cy="${p2y.toFixed(2)}" r="${(stroke * 0.95).toFixed(2)}" fill="#fffbeb"/>
  <text x="${cx}" y="${labelY}" text-anchor="middle"
        font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="${labelSize}"
        letter-spacing="${Math.round(labelSize * 0.3)}" fill="#fde68a">MGF</text>
</svg>`;
}

/** Open Graph / WhatsApp link preview — 1200×630 branded card with MGF mark. */
function openGraphSvg() {
    const width = 1200;
    const height = 630;
    const mark = 280;
    const markX = 120;
    const markY = Math.round((height - mark) / 2);
    const markInner = appIconSvg(mark, { maskable: false, showWordmark: false })
        .replace(/<\?xml[^>]*>/, '')
        .replace(/<svg[^>]*>/, '')
        .replace('</svg>', '');

    return `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <radialGradient id="ogGlow" cx="28%" cy="50%" r="45%">
      <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.28"/>
      <stop offset="55%" stop-color="#f59e0b" stop-opacity="0.08"/>
      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="ogBar" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
  </defs>
  <rect width="${width}" height="${height}" fill="${BG}"/>
  <rect width="${width}" height="${height}" fill="url(#ogGlow)"/>
  <rect x="0" y="0" width="12" height="${height}" fill="url(#ogBar)"/>
  <svg x="${markX}" y="${markY}" width="${mark}" height="${mark}" viewBox="0 0 ${mark} ${mark}">
    ${markInner}
  </svg>
  <text x="460" y="250" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="72" letter-spacing="2" fill="#fffbeb">MGF Workspace</text>
  <text x="460" y="320" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="600" font-size="28" letter-spacing="4" fill="#fbbf24">SEGUIMIENTO FINANCIERO</text>
  <text x="460" y="390" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="500" font-size="26" fill="#94a3b8">Presupuestos · Cotizaciones · Finanzas personales</text>
</svg>`;
}

function brandLogoSvg() {
    return `<svg width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MGF Workspace">
  <defs>
    <linearGradient id="g" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="48" height="48" rx="12" fill="${BG}"/>
  <rect x="14" y="24" width="5.5" height="12" rx="1.6" fill="url(#g)"/>
  <rect x="21.5" y="18" width="5.5" height="18" rx="1.6" fill="url(#g)"/>
  <rect x="29" y="12" width="5.5" height="24" rx="1.6" fill="url(#g)"/>
  <path d="M16.7 24 L24.2 15.5 L34.5 12" fill="none" stroke="#fffbeb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="34.5" cy="12" r="2" fill="#fffbeb"/>
  <text x="60" y="22" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="16" letter-spacing="0.04em" fill="${BG}">MGF</text>
  <text x="60" y="38" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="600" font-size="11" letter-spacing="0.12em" fill="#92400e">WORKSPACE</text>
</svg>`;
}

function brandLogoDarkSvg() {
    return `<svg width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MGF Workspace">
  <defs>
    <linearGradient id="gd" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#d97706"/>
      <stop offset="55%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#fbbf24"/>
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="48" height="48" rx="12" fill="${BG}"/>
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

async function createIcon(size, filename, options = {}) {
    const svg = appIconSvg(size, options);
    await sharp(Buffer.from(svg)).png().toFile(join(outDir, filename));
}

async function createSplash(width, height, filename) {
    const svg = splashSvg(width, height);
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
    const maskable = filename.includes('maskable');
    await createIcon(size, filename, {
        maskable,
        showWordmark: maskable ? false : size >= 128,
    });
}

// iOS launch / splash screens (portrait). Filenames match apple-splash head tags.
const splashes = [
    { w: 1290, h: 2796, file: 'apple-splash-1290x2796.png' }, // 14/15/16 Pro Max
    { w: 1179, h: 2556, file: 'apple-splash-1179x2556.png' }, // 14/15/16 Pro
    { w: 1170, h: 2532, file: 'apple-splash-1170x2532.png' }, // 12/13/14
    { w: 1284, h: 2778, file: 'apple-splash-1284x2778.png' }, // 12/13 Pro Max
    { w: 1125, h: 2436, file: 'apple-splash-1125x2436.png' }, // X / XS / 11 Pro
    { w: 1242, h: 2688, file: 'apple-splash-1242x2688.png' }, // XS Max / 11 Pro Max
    { w: 828, h: 1792, file: 'apple-splash-828x1792.png' }, // XR / 11
    { w: 750, h: 1334, file: 'apple-splash-750x1334.png' }, // 8 / SE
    { w: 1242, h: 2208, file: 'apple-splash-1242x2208.png' }, // 8 Plus
    { w: 2048, h: 2732, file: 'apple-splash-2048x2732.png' }, // iPad Pro 12.9
    { w: 1668, h: 2388, file: 'apple-splash-1668x2388.png' }, // iPad Pro 11
    { w: 1536, h: 2048, file: 'apple-splash-1536x2048.png' }, // iPad
];

for (const splash of splashes) {
    await createSplash(splash.w, splash.h, splash.file);
}

await sharp(join(outDir, 'favicon-32x32.png'))
    .resize(32, 32)
    .toFile(join(outDir, 'favicon.ico'));

writeFileSync(join(brandDir, 'mgf-logo.svg'), brandLogoSvg());
writeFileSync(join(brandDir, 'mgf-logo-dark.svg'), brandLogoDarkSvg());
writeFileSync(join(brandDir, 'mgf-mark.svg'), appIconSvg(128, { maskable: false, showWordmark: true }));

copyFileSync(join(outDir, 'favicon.ico'), join(publicDir, 'favicon.ico'));
copyFileSync(join(outDir, 'favicon-32x32.png'), join(publicDir, 'favicon-32x32.png'));
copyFileSync(join(outDir, 'apple-icon-180x180.png'), join(publicDir, 'apple-touch-icon.png'));
copyFileSync(join(outDir, 'icon-512x512.png'), join(brandDir, 'mgf-icon-512.png'));
copyFileSync(join(outDir, 'icon-512x512-maskable.png'), join(brandDir, 'mgf-icon-512-maskable.png'));

const ogDir = join(publicDir, 'assets', 'graphs', 'web');
mkdirSync(ogDir, { recursive: true });
await sharp(Buffer.from(openGraphSvg())).png().toFile(join(ogDir, 'opengraphs-v2.png'));
copyFileSync(join(ogDir, 'opengraphs-v2.png'), join(ogDir, 'opengraphs.png'));
copyFileSync(join(ogDir, 'opengraphs-v2.png'), join(brandDir, 'mgf-opengraph.png'));

console.log('PWA icons, Apple splash screens, Open Graph preview, and brand logos generated.');
