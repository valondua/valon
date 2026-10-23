const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const sq = JSON.parse(read('assets/albania/sq.json'));
const records = Object.fromEntries(JSON.parse(read('assets/albania/research-register.json')).records.map(r => [r.id, r]));
const translated = (id, field) => sq[records[id][field]];

test('server and browser translation dictionaries agree', () => {
  const context = {window: {}};
  vm.runInNewContext(read('assets/albania/sq.js'), context);
  assert.deepEqual(JSON.parse(JSON.stringify(context.window.albaniaTranslations)), sq);
});
test('media framing and evidence CTA do not imply organisation or concealment', () => {
  assert.equal(sq['Who framed the protests — and how?'], 'Si i paraqitën mediat protestat?');
  assert.equal(sq['Explore the evidence behind each connection →'], 'Eksploroni provat për secilën lidhje →');
});
test('CPI score and CPI/RSF ranks keep their distinct units and numbers', () => {
  const body = translated('R10', 'body');
  assert.match(body, /42\/100 pikësh/);
  assert.match(body, /vendin e 80-të në renditje/);
  assert.match(body, /vendin e 83-të nga 180/);
  assert.doesNotMatch(body, /80-tën pikë|83\/180 pikë/);
  assert.match(translated('R10', 'limit'), /Perceptimit të Korrupsionit \(CPI\)/);
});
test('research leads, demographic measures and draft claims retain their meaning', () => {
  assert.match(translated('R42', 'body'), /Pistat e kërkimit nga komuniteti/);
  assert.match(translated('R42', 'limit'), /nuk janë tregues të këmbyeshëm/);
  assert.match(translated('R37', 'limit'), /pretendime të pakonfirmuara të draftit/);
  assert.doesNotMatch(translated('R37', 'limit'), /projektligj/);
});
test('laundering correction retains allegation, denial and conviction limits', () => {
  assert.match(translated('R33', 'body'), /akuzat për korrupsion, pastrim parash/);
  assert.match(translated('R33', 'body'), /mohimin e tij/);
  assert.match(translated('R33', 'limit'), /mos e etiketoni Ahmetajn si të dënuar/);
});
test('R44 source and rendered text agree and qualify submission verification in both languages', () => {
  const html = read('template-parts/albania-research-register.html');
  const rendered = html.match(/id="r44"[\s\S]*?<strong>Limit:<\/strong> (.*?)<\/p>/)[1];
  assert.equal(rendered, records.R44.limit);
  assert.match(rendered, /English and Albanian submission pages exist/);
  assert.match(rendered, /Neither a public HTTP 200 response nor synthetic delivery alone/);
  assert.match(sq[rendered], /Faqet për dorëzimin e informacionit ekzistojnë në anglisht dhe shqip/);
  assert.match(sq[rendered], /nuk vërteton garanci për privatësinë, sigurinë ose qasjen/);
  assert.doesNotMatch(JSON.stringify(records.R44), /No submission form/);
});
