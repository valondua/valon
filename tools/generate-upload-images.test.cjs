const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');
const {createHash} = require('node:crypto');
const sharp = require('sharp');
const {sourceLocation, validateManifest, buildImages} = require('./generate-upload-images.cjs');
const sha = value => createHash('sha256').update(value).digest('hex');
const sample = {basename:'qa-example',width:10,height:8,widths:[5,10],sha256:'0'.repeat(64)};

test('source roots are explicit and directory identity survives duplicate filenames', () => {
    assert.equal(sourceLocation('2025/01/image.png').local, 'uploads/2025/01/image.png');
    assert.equal(sourceLocation('2025/06/image.png').local, 'uploads/2025/06/image.png');
    assert.equal(sourceLocation('theme:assets/video-covers/123.jpg').local, 'theme/assets/video-covers/123.jpg');
    for (const key of ['https://remote.test/image.png','2025/01/../../image.png','2025/01/moving.gif','theme:assets/other.jpg','2025/01/image.png?x']) {
        assert.throws(() => sourceLocation(key), /Unsupported source key/);
    }
    assert.throws(() => validateManifest({'2025/01/image.png':sample,'2025/06/image.png':sample}), /duplicate output basename/);
    assert.throws(() => validateManifest({'2025/01/image.png':{...sample,widths:[5,11]}}), /Invalid resize width/);
});

test('build verifies content identity and dimensions, never conflates same-name sources, and is reproducible', async () => {
    const dir = await fs.mkdtemp(path.join(os.tmpdir(), 'valon-upload-build-'));
    try {
        const source = path.join(dir,'source'), out = path.join(dir,'output');
        await fs.mkdir(out);
        const manifest = {};
        for (const [month,color] of [['01','red'],['06','blue']]) {
            const key = `2025/${month}/image.png`;
            const image = await sharp({create:{width:10,height:8,channels:3,background:color}}).png().toBuffer();
            const filename = path.join(source, sourceLocation(key).local);
            await fs.mkdir(path.dirname(filename),{recursive:true});await fs.writeFile(filename,image);
            manifest[key] = {...sample,basename:`qa-${month}`,sha256:sha(image)};
        }
        const options = {sourceDirectory:source,outputDirectory:out,log:()=>{}};
        await assert.rejects(buildImages({'2025/01/image.png':{...manifest['2025/01/image.png'],sha256:'0'.repeat(64)}},options), /Original changed/);
        await assert.rejects(buildImages({'2025/01/image.png':{...manifest['2025/01/image.png'],height:9}},options), /Unexpected dimensions/);
        const results = await buildImages(manifest, options);
        const hashes = Object.fromEntries(await Promise.all(results.map(async r=>[r.filename,sha(await fs.readFile(path.join(out,r.filename)))])));
        assert.equal(results.length,8);
        assert.notEqual(hashes['qa-01-10.avif'],hashes['qa-06-10.avif']);
        for (const result of await buildImages(manifest,options)) {
            assert.equal(sha(await fs.readFile(path.join(out,result.filename))),hashes[result.filename]);
            const metadata = await sharp(path.join(out,result.filename)).metadata();
            assert.equal(metadata.width,result.width);assert.equal(metadata.height,result.height);
        }
    } finally {await fs.rm(dir,{recursive:true,force:true});}
});
