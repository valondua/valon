#!/usr/bin/env node
// Build-time only: Sharp 0.35.4. Original uploads stay on WordPress unchanged.
// NODE_PATH=/path/to/node_modules node tools/generate-upload-images.cjs /path/to/source-directory
// The optional directory contains the original filenames; otherwise fetch the exact public originals.
const sharp = require('sharp');
const fs = require('node:fs/promises');
const path = require('node:path');
const {createHash} = require('node:crypto');
const manifest = require('../assets/optimized-uploads.json');
const assets = path.resolve(__dirname, '../assets');

async function main() {
    for (const [relative, image] of Object.entries(manifest)) {
        let source;
        if (process.argv[2]) {
            source = await fs.readFile(path.join(process.argv[2], path.basename(relative)));
        } else {
            const response = await fetch(`https://www.valonasani.com/wp-content/uploads/${relative}`);
            if (!response.ok) throw new Error(`Original unavailable: ${relative}`);
            source = Buffer.from(await response.arrayBuffer());
        }
        if (createHash('sha256').update(source).digest('hex') !== image.sha256) {
            throw new Error(`Original changed; review before rebuilding: ${relative}`);
        }
        const metadata = await sharp(source).metadata();
        if (metadata.width !== image.width || metadata.height !== image.height || (metadata.pages || 1) !== 1) {
            throw new Error(`Unexpected dimensions or animation: ${relative}`);
        }
        for (const width of image.widths) {
            if (width > image.width) throw new Error(`Refusing to upscale ${relative}`);
            const resized = sharp(source).rotate().resize({width, withoutEnlargement: true}).toColourspace('srgb');
            for (const extension of ['avif', 'webp']) {
                const output = resized.clone();
                if (extension === 'avif') output.avif({quality: 60, effort: 6, chromaSubsampling: '4:4:4'});
                else output.webp({quality: 82, effort: 6, smartSubsample: true});
                const filename = `${image.basename}-${width}.${extension}`;
                const result = await output.toFile(path.join(assets, filename));
                console.log(`${filename}: ${result.size} bytes`);
            }
        }
    }
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
