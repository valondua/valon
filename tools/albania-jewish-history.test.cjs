const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const read=f=>fs.readFileSync(require('node:path').join(__dirname,'..',f),'utf8');
const html=read('template-parts/albania-jewish-history-projects.html');
const sq=JSON.parse(read('assets/albania/sq.json'));
const decode=s=>s.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
test('one static accessible section, one navigation entry per desktop/mobile surface',()=>{
 assert.equal((html.match(/<section\b/g)||[]).length,1);
 assert.match(html,/id="jewish-history-projects" tabindex="-1"/);
 const main=read('template-parts/albania-investigation.html');
 assert.equal((main.match(/href="#jewish-history-projects"/g)||[]).length,1);
 assert.equal((main.match(/value="#jewish-history-projects"/g)||[]).length,1);
 assert.match(main,/class="demands-header" href="#demands"/);
 assert.match(main,/class="demands-hero" href="#demands"/);
 assert.match(read('page-albania-protests-investigation.php'),/albania-jewish-history-projects.html/);
 assert.doesNotMatch(html,/<(?:script|iframe|img)\b/);
});
test('every visible context text node has an explicit Albanian mapping',()=>{
 const nodes=[...html.matchAll(/>([^<>]+)</g)].map(m=>decode(m[1]).trim()).filter(s=>s && s!=='·');
 for(const text of nodes) assert.ok(sq[text],`Missing SQ: ${text}`);
});
test('IDs unique and existing evidence links retained',()=>{
 const ids=[...html.matchAll(/id="([^"]+)"/g)].map(m=>m[1]);
 assert.equal(new Set(ids).size,ids.length);
 for(const id of ['r18','r20','r27'])assert.ok(html.includes(`href="#${id}"`));
});
test('homeland language discrepancy and separate ceremony/publication dates survive',()=>{
 assert.match(html,/reserve homeland for the Jewish people/);
 assert.match(html,/një atdhe i dytë për hebrenjtë/);
 assert.match(html,/original audio has not been checked/);
 assert.match(html,/Ceremony: 6 April; government transcript: 7 April 2025/);
 assert.match(html,/No online facsimile was inspected/);
});
test('financial units, consultation chronology and historical limits stay explicit in both languages',()=>{
 for(const term of ['USD 3 million','USD 6.5 million','EUR 9 million','USD 200,000','458 m²','41–0','158','162'])assert.ok(html.includes(term),term);
 for(const term of ['pa TVSH','6.5 milionë USD','9 milionë EUR','200,000 USD','458 m²','41–0','një dëgjesë ishte zhvilluar tashmë','familjes Arditi'])assert.ok(Object.values(sq).some(s=>s.includes(term)),term);
 assert.match(html,/does not establish that consultation began after construction/);
 assert.match(html,/No unverified percentage/);
 assert.match(html,/Arditi/);
});
