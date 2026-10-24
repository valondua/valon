const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const read=f=>fs.readFileSync(require('node:path').join(__dirname,'..',f),'utf8');
const html=read('template-parts/albania-hyland.html'),main=read('template-parts/albania-investigation.html'),js=read('assets/albania/investigation.js'),sq=JSON.parse(read('assets/albania/sq.json'));
const decode=s=>s.replace(/&amp;/g,'&').replace(/&#x27;/g,"'").replace(/&quot;/g,'"');
test('Hyland section is navigable on both surfaces and included by PHP',()=>{
 assert.match(html,/id="christopher-hyland" tabindex="-1"/);
 assert.equal((main.match(/href="#christopher-hyland"/g)||[]).length,6);
 assert.equal((main.match(/value="#christopher-hyland"/g)||[]).length,1);
 assert.match(read('page-albania-protests-investigation.php'),/albania-hyland.html/);
 assert.match(read('assets/albania/demands.js'),/'#christopher-hyland'/);
});
test('all section copy is translated and profile has safe no-photo fallback',()=>{
 for(const m of html.matchAll(/>([^<>]+)</g)){const text=decode(m[1]).trim();if(text&&text!=='·')assert.ok(sq[text],text);}
 const profiles=JSON.parse(read('assets/albania/profiles-data.json'));
 for(const id of ['hyland','baba_mondi','hazis_vardar','orthodox_idea','rugova','jim_xhema','stephanopoulos']){assert.ok(sq[profiles[id].role]);assert.equal(profiles[id].photo,null);}
});
test('graph distinguishes a sourced historical idea from an existing state',()=>{
 const nodes=vm.runInNewContext(js.match(/const nodes=(.*?);/s)[1]);
 const edges=vm.runInNewContext(js.match(/const edges=(.*?);\n/s)[1]);
 const sources=vm.runInNewContext('('+js.match(/const edgeSources=(.*?);/s)[1]+')');
 const he=edges.filter(e=>e[0]==='hyland'||e[1]==='hyland');assert.equal(he.length,8);
 for(const e of he){assert.ok(nodes.some(n=>n[0]===e[1]));assert.ok(sources[e[0]+'|'+e[1]]);assert.ok(sq[e[2]]);}
 assert.equal(he.find(e=>e[1]==='bektashi')[3],'Reported');
 assert.equal(he.find(e=>e[1]==='kurti')[3],'Documented');
 const idea=he.find(e=>e[1]==='orthodox_idea');assert.equal(idea[3],'Documented');assert.match(idea[2],/2001 capture/);assert.match(sources['hyland|orthodox_idea'],/20010817125434/);
});
test('archive evidence corrects the old claim without inventing the full proposal or its date',()=>{
 assert.match(html,/historical advocacy verified/);assert.match(html,/not a verified date of authorship or submission/);assert.match(html,/not located a detailed original proposal/);assert.match(html,/Correction · 24 October 2026/);
 for(const id of ['archive','recollection','kurti','bektashi','claim'])assert.equal((main.match(new RegExp('id="event-hyland-'+id+'"','g'))||[]).length,1);
 assert.match(main,/This is an archive date, not the proposal’s authorship or submission date/);
 assert.doesNotMatch(html,/We do not show this as an established connection/);
});

test('retrospective people are reported contacts, not established backers or direct idea endorsements',()=>{
 const edges=vm.runInNewContext(js.match(/const edges=(.*?);\n/s)[1]);
 for(const id of ['rugova','jim_xhema','stephanopoulos']){
  const links=edges.filter(e=>e[0]===id||e[1]===id);assert.equal(links.length,1);assert.equal(links[0][0],'hyland');assert.equal(links[0][3],'Reported');assert.match(links[0][2],/2020 recollection/);
  assert.ok(read('includes/albania-dossier.php').includes("'"+id+"'"));
 }
 assert.match(html,/We verified the published account, not the private discussions/);
 assert.match(html,/No independently corroborated co-author, funder or institutional backer/);
 assert.match(main,/Publication date, not a newly verified 1992 event/);
});

test('composed retrospective connection text translates in Albanian',()=>{
 const sandbox={window:{albaniaTranslations:sq},document:{body:{nodeType:0},addEventListener(){}},MutationObserver:class{observe(){}}};
 vm.runInNewContext(read('assets/albania/locale.js'),sandbox);
 for(const label of ['Discussed autonomy','Made an introduction','Resisted proposal'])assert.equal(sandbox.window.albaniaTranslate('Christopher Hyland → Ibrahim Rugova · '+label+' · 2020 recollection'),'Christopher Hyland → Ibrahim Rugova · '+sq[label]+' · '+sq['2020 recollection']);
});
