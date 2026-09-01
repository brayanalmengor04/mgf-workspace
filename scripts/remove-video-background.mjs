/**
 * Genera un WebM con canal alpha a partir del video del banner (fondo blanco).
 * Requiere ffmpeg en PATH o el binario de ffmpeg-static.
 *
 * Uso: npm run video:remove-bg
 */

import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

const input = resolve(root, 'public/videos/budget-scan-pen.mp4');
const output = resolve(root, 'public/videos/budget-scan-pen-alpha.webm');

async function resolveFfmpeg() {
    try {
        const ffmpegStatic = await import('ffmpeg-static');

        if (typeof ffmpegStatic.default === 'string' && existsSync(ffmpegStatic.default)) {
            return ffmpegStatic.default;
        }
    } catch {
        // Optional dependency.
    }

    return 'ffmpeg';
}

async function main() {
    if (! existsSync(input)) {
        console.error(`No se encontró el video de entrada: ${input}`);
        process.exit(1);
    }

    const ffmpeg = await resolveFfmpeg();

    const args = [
        '-y',
        '-i',
        input,
        '-vf',
        'colorkey=0xFFFFFF:0.22:0.08,format=rgba',
        '-c:v',
        'libvpx-vp9',
        '-pix_fmt',
        'yuva420p',
        '-auto-alt-ref',
        '0',
        '-b:v',
        '0',
        '-crf',
        '32',
        '-an',
        output,
    ];

    console.log('Procesando con FFmpeg (chroma key blanco → alpha)...');
    const result = spawnSync(ffmpeg, args, { stdio: 'inherit' });

    if (result.status !== 0) {
        console.error('\nFalló FFmpeg. Instala ffmpeg o ejecuta: npm i -D ffmpeg-static');
        process.exit(result.status ?? 1);
    }

    console.log(`\nListo: ${output}`);
    console.log('Tip: añade <source src="...budget-scan-pen-alpha.webm" type="video/webm"> como primera fuente.');
}

main();
