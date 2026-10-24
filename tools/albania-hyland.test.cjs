const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const read=f=>fs.readFileSync(require('node:path').join(__dirname,'..',f),'utf8');
const html=read('template-parts/albania-hyland.html'),main=read('template-parts/albania-investigation.html'),js=read('assets/albania/investigation.js'),sq=JSON.parse(read('assets/albania/sq.json'));
const decode=s=>s.replace(/&amp;/g,'&').replace(/&#x27;/g,"'").replace(/&quot;/g,'"');
test('Hyland section is navigable on both surfaces and included by PHP',()=>{
 assert.match(html,/id="christopher-hyland" tabindex="-1"/);
 assert.equal((main.match(/href="#christopher-hyland"/g)||[]).length,4);
 assert.equal((main.match(/value="#christopher-hyland"/g)||[]).length,1);
 assert.match(read('page-albania-protests-investigation.php'),/albania-hyland.html/);
 assert.match(read('assets/albania/demands.js'),/'#christopher-hyland'/);
});
test('all section copy is translated and profile has safe no-photo fallback',()=>{
 for(const m of html.matchAll(/>([^<>]+)</g)){const text=decode(m[1]).trim();if(text&&text!=='·')assert.ok(sq[text],text);}
 const profiles=JSON.parse(read('assets/albania/profiles-data.json'));
 for(const id of ['hyland','baba_mondi','hazis_vardar']){assert.ok(sq[profiles[id].role]);assert.equal(profiles[id].photo,null);}
});
test('graph contains only four explicitly sourced Hyland links, no Orthodox-state edge',()=>{
 const nodes=vm.runInNewContext(js.match(/const nodes=(.*?);/s)[1]);
 const edges=vm.runInNewContext(js.match(/const edges=(.*?);\n/s)[1]);
 const sources=vm.runInNewContext('('+js.match(/const edgeSources=(.*?);/s)[1]+')');
 const he=edges.filter(e=>e[0]==='hyland'||e[1]==='hyland');assert.equal(he.length,4);
 for(const e of he){assert.ok(nodes.some(n=>n[0]===e[1]));assert.ok(sources[e[0]+'|'+e[1]]);assert.ok(sq[e[2]]);assert.doesNotMatch(e.join(' '),/Orthodox|Crawford/);}
 assert.equal(he.find(e=>e[1]==='bektashi')[3],'Reported');
 assert.equal(he.find(e=>e[1]==='kurti')[3],'Documented');
});
test('allegation retains missing-source caveat and timeline dates represent publication',()=>{
 assert.match(html,/Unverified allegation/);assert.match(html,/no retrievable original proposal/);assert.match(html,/does not settle what an earlier proposal/);
 for(const id of ['kurti','bektashi','claim'])assert.equal((main.match(new RegExp('id="event-hyland-'+id+'"','g'))||[]).length,1);
 assert.match(main,/Publication date, not the date of an authenticated Orthodox-state proposal/);
});
