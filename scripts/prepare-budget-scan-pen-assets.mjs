import sharp from 'sharp';
import { copyFileSync, mkdirSync, readdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();
const framesSrc = join(
    root,
    'Pero_sin_los_numeros_porfa_deb_processed_frames_inspyrenet - copia',
    'processed_frames',
);
const gifSrc = join(
    root,
    'Pero_sin_los_numeros_porfa_deb_processed_inspyrenet_q80_t95 - copia.gif',
);
const outRoot = join(root, 'public', 'animations', 'budget-scan-pen');
const framesOut = join(outRoot, 'frames');

const FRAME_STEP = 3;
const FRAME_WIDTH = 720;
const WEBP_QUALITY = 82;

mkdirSync(framesOut, { recursive: true });
copyFileSync(gifSrc, join(outRoot, 'budget-scan-pen-hero.gif'));

const files = readdirSync(framesSrc)
    .filter((file) => file.endsWith('.png'))
    .sort();

let outputIndex = 0;

for (let index = 0; index < files.length; index += FRAME_STEP) {
    const source = join(framesSrc, files[index]);
    const target = join(framesOut, `frame_${String(outputIndex).padStart(4, '0')}.webp`);

    await sharp(source)
        .resize({ width: FRAME_WIDTH, withoutEnlargement: true })
        .webp({ quality: WEBP_QUALITY, alphaQuality: 90 })
        .toFile(target);

    outputIndex += 1;
    process.stdout.write(`\rPrepared frame ${outputIndex}/${Math.ceil(files.length / FRAME_STEP)}`);
}

writeFileSync(
    join(outRoot, 'manifest.json'),
    JSON.stringify(
        {
            frameCount: outputIndex,
            fps: 20,
            width: FRAME_WIDTH,
            gif: '/animations/budget-scan-pen/budget-scan-pen-hero.gif',
            framesBase: '/animations/budget-scan-pen/frames/frame_',
            framesExt: '.webp',
        },
        null,
        2,
    ),
);

console.log(`\nDone: ${outputIndex} WebP frames + budget-scan-pen-hero.gif`);
