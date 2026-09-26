#!/usr/bin/env node
// Requires sharp 0.35.4 (Node.js build-time tool only).
// Example: npm install --prefix /tmp/valon-image-tools sharp@0.35.4
// NODE_PATH=/tmp/valon-image-tools/node_modules node tools/generate-modern-images.cjs
// Pass image names to rebuild only those sources, for example: valon-travel valon-reading.
// Hero WebP fallbacks are rebuilt separately by generate-hero-images.sh.
const sharp = require('sharp');
const path = require('node:path');

const images = {
    'valon-hero': [480, 768, 1024, 1586],
    'valon-candid': [360],
    'valon-self-respect': [480, 768, 1024],
    'valon-dua': [480, 768, 1024],
    'valon-portrait': [480, 768, 879],
    'valon-travel': [480, 768],
    'valon-reading': [480, 768, 792],
    'valon-friends': [480, 768, 1024],
};
const assets = path.resolve(__dirname, '../assets');

async function main() {
    const requested = process.argv.slice(2);
    for (const name of requested) {
        if (!Object.hasOwn(images, name)) throw new Error(`Unknown bundled image: ${name}`);
    }
    for (const [name, widths] of Object.entries(images)) {
        if (requested.length && !requested.includes(name)) continue;
        const source = path.join(assets, `${name}.jpeg`);
        const metadata = await sharp(source).metadata();
        for (const width of widths) {
            if (width > metadata.width) throw new Error(`Refusing to upscale ${name}`);
            // Convert embedded colour profiles to sRGB before removing metadata.
            // Preserve the full photograph; the existing CSS controls the crop.
            const image = sharp(source).rotate().resize({width, withoutEnlargement: true}).toColourspace('srgb');
            const avif = await image.clone().avif({quality: 55, effort: 6, chromaSubsampling: '4:4:4'})
                .toFile(path.join(assets, `${name}-${width}.avif`));
            console.log(`${name}-${width}.avif: ${avif.size} bytes`);
            if (name !== 'valon-hero') {
                const webp = await image.clone().webp({quality: 80, effort: 6, smartSubsample: true})
                    .toFile(path.join(assets, `${name}-${width}.webp`));
                console.log(`${name}-${width}.webp: ${webp.size} bytes`);
            }
        }
    }
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
