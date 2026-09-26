const assert=require('node:assert/strict');const fs=require('node:fs/promises');const path=require('node:path');const os=require('node:os');const {createHash}=require('node:crypto');const sharp=require('sharp');
const {validateSources,verifyOriginal,quality,buildImages}=require('./generate-remote-images.cjs');
(async()=>{const dir=await fs.mkdtemp(path.join(os.tmpdir(),'valon-remote-qa-'));try{
 const source=await sharp({create:{width:8,height:6,channels:3,background:{r:120,g:160,b:200}}}).png().toBuffer();const hash=createHash('sha256').update(source).digest('hex');const url='https://lh7-us.googleusercontent.com/reviewed-fixture';const pin={basename:`valon-remote-${hash.slice(0,12)}`,sha256:hash,bytes:source.length,width:8,height:6,hasAlpha:false};
 validateSources({[url]:pin});await verifyOriginal(source,pin);
 for(const invalid of [url+'?x=1',url+'#fragment','https://evil.example/image','https://lh7-us.googleusercontent.com.evil.test/image'])assert.throws(()=>validateSources({[invalid]:pin}));
 for(const invalid of [{...pin,sha256:'0'.repeat(64)},{...pin,width:9},{...pin,hasAlpha:true}])await assert.rejects(verifyOriginal(source,invalid));
 const changed=await sharp(source).resize(4,3).webp().toBuffer();await assert.rejects(quality(source,changed,pin));
 // A changed downloaded/local original must never overwrite the prior manifest.
 const out=path.join(dir,'assets');await fs.mkdir(out);await fs.writeFile(path.join(out,'optimized-remote-images.json'),'previous reviewed manifest\n');await fs.writeFile(path.join(dir,pin.basename+'.png'),Buffer.concat([source,Buffer.from('changed')]));
 await assert.rejects(buildImages({[url]:pin},{sourceDirectory:dir,outputDirectory:out}));assert.equal(await fs.readFile(path.join(out,'optimized-remote-images.json'),'utf8'),'previous reviewed manifest\n');assert.deepEqual(await fs.readdir(out),['optimized-remote-images.json']);
 console.log('PASS exact URL guard, pinned source SHA/metadata, output dimensions, and no publication on changed original');
}finally{await fs.rm(dir,{recursive:true,force:true});}})().catch(e=>{console.error(e);process.exitCode=1});
