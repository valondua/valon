const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const read=f=>fs.readFileSync(require('node:path').join(__dirname,'..',f),'utf8');
const html=read('template-parts/albania-protected-areas-law.html'),main=read('template-parts/albania-investigation.html'),js=read('assets/albania/investigation.js'),sq=JSON.parse(read('assets/albania/sq.json'));
const decode=s=>s.replace(/&amp;/g,'&').replace(/&#x27;/g,"'").replace(/&quot;/g,'"');
test('law explainer and dated events are reachable in both language route maps',()=>{
 assert.match(read('includes/albania-dossier.php'),/\['story','protected-areas-law','geography'\]/);
 assert.match(read('page-albania-protests-investigation.php'),/albania-protected-areas-law.html/);
 assert.match(main,/<!-- ALBANIA_PROTECTED_AREAS -->/);
 for(const id of ['draft','adoption','court'])assert.match(main,new RegExp('id="event-law21-'+id+'"'));
});
test('every law paragraph and navigation label has an explicit Albanian translation',()=>{
 for(const m of html.matchAll(/>([^<>]+)</g)){const text=decode(m[1]).trim();if(text&&text!=='·')assert.ok(sq[text],text);}
 assert.ok(sq['Explore the law']);
});
test('draft, vote reporting, court outcome and permit limits remain distinct',()=>{
 for(const phrase of ['Sponsorship is not an individual voting record','74 for, 0 against, 0 abstentions','Individual votes and absences: not verified','37 MPs','five-judge majority','not a blanket ban on all tourism','does not name Jared Kushner','Do not quote that as the final law'])assert.ok(html.includes(phrase),phrase);
 assert.match(html,/20231203012904/);assert.match(html,/202402271348113540/);
});
test('all new graph links are directly sourced legal roles with bilingual labels',()=>{
 const nodes=vm.runInNewContext(js.match(/const nodes=(.*?);/s)[1]);const edges=vm.runInNewContext(js.match(/const edges=(.*?);\n/s)[1]);const sources=vm.runInNewContext('('+js.match(/const edgeSources=(.*?);/s)[1]+')');
 const related=edges.filter(e=>e[0]==='law21'||e[1]==='law21');assert.equal(related.length,5);
 for(const e of related){assert.ok(nodes.some(n=>n[0]===e[0]));assert.ok(nodes.some(n=>n[0]===e[1]));assert.ok(sources[e[0]+'|'+e[1]]);assert.ok(sq[e[2]]);assert.equal(e[3],'Documented');assert.ok(!e.includes('kushner'));}
 for(const id of ['law21','kkt','nasufi','kumbaro','zhupa']){const profile=JSON.parse(read('assets/albania/profiles-data.json'))[id];assert.ok(sq[profile.role]);assert.equal(profile.photo,null);}
});

test('official totals do not become inferred individual votes or absences',()=>{
 assert.match(html,/202403051328240902Procesverbal/);
 assert.ok(html.includes('assign no MP to a for, against, abstaining, not-voting or absent category'));
 assert.ok(html.includes('not evidence of three abstentions'));
 assert.ok(!html.includes('74 Socialist MPs voting'));
 assert.ok(main.includes('Official parliamentary minutes record the final vote'));
 const context={window:{}};vm.runInNewContext(read('assets/albania/sq.js'),context);
 assert.equal(JSON.stringify(context.window.albaniaTranslations),JSON.stringify(sq));
});
