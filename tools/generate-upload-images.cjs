#!/usr/bin/env node
// Build-time only: Sharp 0.35.4. Original uploads and theme photos stay unchanged.
// NODE_PATH=/path/to/node_modules node tools/generate-upload-images.cjs [source-directory]
// Local sources mirror uploads/YYYY/MM/filename or theme/assets/video-covers/filename.
const sharp = require('sharp');
const fs = require('node:fs/promises');
const path = require('node:path');
const {createHash} = require('node:crypto');
const theme = path.resolve(__dirname, '..');

function sourceLocation(key) {
    if (/^theme:assets\/video-covers\/[0-9]+\.jpg$/.test(key)) {
        const relative = key.slice('theme:'.length);
        return {local: path.join('theme', relative), bundled: path.join(theme, relative)};
    }
    if (/^[0-9]{4}\/[0-9]{2}\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g)$/.test(key)) {
        return {local: path.join('uploads', key), url: `https://www.valonasani.com/wp-content/uploads/${key}`};
    }
    throw new Error(`Unsupported source key: ${key}`);
}

function validateManifest(manifest) {
    const basenames = new Set();
    for (const [key, image] of Object.entries(manifest)) {
        sourceLocation(key);
        if (!/^[a-z0-9][a-z0-9-]*$/.test(image.basename) || basenames.has(image.basename)) {
            throw new Error(`Invalid or duplicate output basename: ${key}`);
        }
        basenames.add(image.basename);
        if (!/^[a-f0-9]{64}$/.test(image.sha256) || !Number.isInteger(image.width) || !Number.isInteger(image.height) || image.width < 1 || image.height < 1) {
            throw new Error(`Invalid source identity: ${key}`);
        }
        let previous = 0;
        if (!Array.isArray(image.widths) || image.widths.length === 0) throw new Error(`Missing widths: ${key}`);
        for (const width of image.widths) {
            if (!Number.isInteger(width) || width <= previous || width > image.width) throw new Error(`Invalid resize width: ${key}`);
            previous = width;
        }
    }
}

async function buildImages(manifest, {sourceDirectory, outputDirectory = path.join(theme, 'assets'), log = console.log} = {}) {
    validateManifest(manifest);
    const results = [];
    for (const [key, image] of Object.entries(manifest)) {
        const location = sourceLocation(key);
        let source;
        if (sourceDirectory || location.bundled) {
            source = await fs.readFile(sourceDirectory ? path.join(sourceDirectory, location.local) : location.bundled);
        } else {
            const response = await fetch(location.url);
            if (!response.ok) throw new Error(`Original unavailable: ${key}`);
            source = Buffer.from(await response.arrayBuffer());
        }
        if (createHash('sha256').update(source).digest('hex') !== image.sha256) {
            throw new Error(`Original changed; review before rebuilding: ${key}`);
        }
        const metadata = await sharp(source).metadata();
        if (metadata.width !== image.width || metadata.height !== image.height || (metadata.pages || 1) !== 1 || (metadata.orientation || 1) !== 1) {
            throw new Error(`Unexpected dimensions, orientation or animation: ${key}`);
        }
        for (const width of image.widths) {
            const resized = sharp(source).resize({width, withoutEnlargement: true}).toColourspace('srgb');
            for (const extension of ['avif', 'webp']) {
                const output = resized.clone();
                if (extension === 'avif') output.avif({quality: 60, effort: 6, chromaSubsampling: '4:4:4'});
                else output.webp({quality: 82, effort: 6, smartSubsample: true});
                const filename = `${image.basename}-${width}.${extension}`;
                const result = await output.toFile(path.join(outputDirectory, filename));
                results.push({key, filename, bytes: result.size, width: result.width, height: result.height});
                log(`${filename}: ${result.size} bytes`);
            }
        }
    }
    return results;
}

module.exports = {sourceLocation, validateManifest, buildImages};
if (require.main === module) {
    buildImages(require('../assets/optimized-uploads.json'), {sourceDirectory: process.argv[2]})
        .catch(error => { console.error(error.message); process.exitCode = 1; });
}
