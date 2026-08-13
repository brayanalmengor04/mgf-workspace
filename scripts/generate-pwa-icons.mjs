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

function bMonogramGroup(scale, offsetX = 0, offsetY = 0) {
    return `<g transform="translate(${offsetX}, ${offsetY}) scale(${scale})">
  <path d="M11 6 L11 42 L11 6 Z" fill="#fffbeb"/>
  <path d="M13.5 10 C25 8.5 31 14.5 28.5 18.5 C26 21.5 13.5 20.5 13.5 20.5 L13.5 10 Z" fill="#cbd5e1"/>
  <path d="M13.5 23 C29 22.5 35.5 29 32.5 35 C29.5 40.5 13.5 39 13.5 39 L13.5 23 Z" fill="#fbbf24"/>
  <path d="M6 25 Q17 21 30 24 T38 27" stroke="rgba(255,251,235,0.45)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
</g>`;
}

function bMonogramGroupDark(scale, offsetX = 0, offsetY = 0) {
    return `<g transform="translate(${offsetX}, ${offsetY}) scale(${scale})">
  <path d="M11 6 L11 42 L11 6 Z" fill="#1e3347"/>
  <path d="M13.5 10 C25 8.5 31 14.5 28.5 18.5 C26 21.5 13.5 20.5 13.5 20.5 L13.5 10 Z" fill="#2c4258"/>
  <path d="M13.5 23 C29 22.5 35.5 29 32.5 35 C29.5 40.5 13.5 39 13.5 39 L13.5 23 Z" fill="#a67c3a"/>
  <path d="M6 25 Q17 21 30 24 T38 27" stroke="#94a3b8" stroke-width="1.3" fill="none" stroke-linecap="round"/>
</g>`;
}

/**
 * B monogram app icon. Mobile-first:
 * - any: full-bleed slate square (Apple/Android home)
 * - maskable: 24% safe padding (survives circular crop)
 */
function appIconSvg(size, { maskable = false, showWordmark = null } = {}) {
    const safe = maskable ? 0.24 : 0.08;
    const inset = size * safe;
    const tile = size - inset * 2;
    const includeWordmark = showWordmark ?? (! maskable && size >= 128);
    const markScale = tile / 48;
    const markX = inset;
    const markY = inset + (includeWordmark ? tile * 0.02 : tile * 0.04);
    const cx = inset + tile / 2;

    const label = includeWordmark
        ? `<text x="${cx}" y="${(inset + tile * 0.9).toFixed(2)}" text-anchor="middle"
                font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
                font-weight="800" font-size="${(tile * 0.11).toFixed(2)}"
                letter-spacing="${(tile * 0.045).toFixed(2)}" fill="#fde68a">MGF</text>`
        : '';

    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <rect width="${size}" height="${size}" fill="${BG}"/>
  ${bMonogramGroup(markScale, markX, markY)}
  ${label}
</svg>`;
}

/** Splash: dark canvas + oversized B monogram. */
function splashSvg(width, height) {
    const mark = Math.round(Math.min(width * 0.42, height * 0.28));
    const cx = width / 2;
    const cy = height / 2 - height * 0.04;
    const scale = mark / 48;
    const markX = cx - mark / 2;
    const markY = cy - mark / 2;
    const labelY = cy + mark * 0.62;
    const labelSize = Math.round(mark * 0.22);

    return `<svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">
  <rect width="${width}" height="${height}" fill="${BG}"/>
  ${bMonogramGroup(scale, markX, markY)}
  <text x="${cx}" y="${labelY}" text-anchor="middle"
        font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="${labelSize}"
        letter-spacing="${Math.round(labelSize * 0.28)}" fill="#fde68a">MGF</text>
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
  ${bMonogramGroupDark(1, 0, 0)}
  <text x="60" y="22" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="800" font-size="16" letter-spacing="0.04em" fill="#0f172a">MGF</text>
  <text x="60" y="38" font-family="ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif"
        font-weight="600" font-size="11" letter-spacing="0.12em" fill="#92400e">WORKSPACE</text>
</svg>`;
}

function brandLogoDarkSvg() {
    return `<svg width="220" height="48" viewBox="0 0 220 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MGF Workspace">
  ${bMonogramGroup(1, 0, 0)}
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
