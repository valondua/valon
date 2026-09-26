#!/usr/bin/env node
// Build-time only. Sharp0.35.4; reviewed exact public URLs and SHA256 pins.
// NODE_PATH=/path/to/node_modules node tools/generate-remote-images.cjs [source-dir] [evidence-dir]
// Local source files use each pinned basename + .png. Never fetches at WordPress runtime.
const fs=require('node:fs/promises');const path=require('node:path');const {createHash}=require('node:crypto');const sharp=require('sharp');
const theme=path.resolve(__dirname,'..');
const ENCODERS={avif:{quality:75,effort:6,chromaSubsampling:'4:4:4'},webp:{quality:90,effort:6,smartSubsample:true}};
const sha=data=>createHash('sha256').update(data).digest('hex');
function validateSources(sources){
 const names=new Set();
 for(const [url,pin] of Object.entries(sources)){
  const u=new URL(url);
  if(u.origin!=='https://lh7-us.googleusercontent.com'||u.search||u.hash||u.username||u.password||!/^\/[A-Za-z0-9_-]+$/.test(u.pathname))throw Error('Unexpected exact source URL');
  if(!/^[a-f0-9]{64}$/.test(pin.sha256)||pin.basename!==`valon-remote-${pin.sha256.slice(0,12)}`||names.has(pin.basename))throw Error('Invalid or duplicate pinned source identity');
  if(![pin.bytes,pin.width,pin.height].every(n=>Number.isInteger(n)&&n>0)||typeof pin.hasAlpha!=='boolean')throw Error('Invalid pinned source metadata');
  names.add(pin.basename);
 }
 if(!names.size)throw Error('Empty source allowlist');
}
async function verifyOriginal(data,pin){
 if(data.length!==pin.bytes||sha(data)!==pin.sha256)throw Error('Original changed; review source before rebuilding');
 const m=await sharp(data).metadata();
 if(m.format!=='png'||m.width!==pin.width||m.height!==pin.height||(m.pages||1)!==1||(m.orientation||1)!==1||m.hasAlpha!==pin.hasAlpha)throw Error('Original dimensions, format, orientation or animation differ');
 return m;
}
async function quality(source,encoded,pin){
 const m=await sharp(encoded).metadata();
 if(m.width!==pin.width||m.height!==pin.height||(m.pages||1)!==1||m.hasAlpha!==pin.hasAlpha)throw Error('Output geometry, alpha or animation differ');
 const original=await sharp(source).toColourspace('srgb').ensureAlpha().raw().toBuffer();
 const output=await sharp(encoded).toColourspace('srgb').ensureAlpha().raw().toBuffer();
 if(original.length!==output.length)throw Error('Decoded output dimensions differ');
 let squared=0;
 for(let i=0;i<original.length;i++){if(i%4===3){if(original[i]!==output[i])throw Error('Alpha changed');}else squared+=(original[i]-output[i])**2;}
 const mse=squared/(original.length/4*3);const psnr=mse?10*Math.log10(255*255/mse):Infinity;
 if(psnr<40||encoded.length>=source.length*.85)throw Error('Output fails quality or15% saving requirement');
 return {width:m.width,height:m.height,hasAlpha:m.hasAlpha,psnr:Number.isFinite(psnr)?psnr:'lossless',bytes:encoded.length,sha256:sha(encoded),saving:1-encoded.length/source.length};
}
async function buildImages(sources,{sourceDirectory,outputDirectory=path.join(theme,'assets'),evidenceDirectory}={}){
 validateSources(sources);const pending=[];const evidence=[];const manifest={};
 // Conversion stays sequential. Validate every output before publishing any manifest.
 for(const [url,pin] of Object.entries(sources)){
  let source;
  if(sourceDirectory)source=await fs.readFile(path.join(sourceDirectory,pin.basename+'.png'));
  else{const r=await fetch(url,{redirect:'error',signal:AbortSignal.timeout(60000)});if(!r.ok||!r.headers.get('content-type')?.startsWith('image/png'))throw Error('Original unavailable or unexpected MIME');source=Buffer.from(await r.arrayBuffer());}
  await verifyOriginal(source,pin);const entry={url,...pin,formats:{}};
  for(const [format,options] of Object.entries(ENCODERS)){
   const bytes=await sharp(source).toColourspace('srgb')[format](options).toBuffer();const check=await quality(source,bytes,pin);const filename=`${pin.basename}.${format}`;
   entry.formats[format]={filename,...check};pending.push({filename,bytes});
  }
  manifest[url]={...pin};evidence.push(entry);
 }
 await fs.mkdir(outputDirectory,{recursive:true});
 for(const file of pending)await fs.writeFile(path.join(outputDirectory,file.filename),file.bytes);
 const manifestPath=path.join(outputDirectory,'optimized-remote-images.json');const temporary=manifestPath+`.${process.pid}.tmp`;
 await fs.writeFile(temporary,JSON.stringify(manifest,null,2)+'\n');await fs.rename(temporary,manifestPath);
 if(evidenceDirectory){await fs.mkdir(evidenceDirectory,{recursive:true});await fs.writeFile(path.join(evidenceDirectory,'generation-report.json'),JSON.stringify({sharp:sharp.versions.sharp,encoders:ENCODERS,images:evidence},null,2)+'\n');}
 return evidence;
}
module.exports={validateSources,verifyOriginal,quality,buildImages};
if(require.main===module)buildImages(require('./remote-image-sources.json'),{sourceDirectory:process.argv[2],evidenceDirectory:process.argv[3]}).then(rows=>console.log(JSON.stringify(rows,null,2))).catch(e=>{console.error(e.message);process.exitCode=1});
