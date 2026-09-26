const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('plugins/valon-platform/assets/yoast-content.js', 'utf8');
function bridge(template, ready = true) {
  let modification, registrations = 0, onReady;
  const app = {
    registerPlugin() { registrations++; },
    registerModification(name, fn) { assert.equal(name, 'content'); modification = fn; },
  };
  const context = { valonYoastTemplate: template, YoastSEO: ready ? { app } : {}, jQuery: () => ({ on: (_, fn) => { onReady = fn; } }) };
  context.window = context;
  vm.runInNewContext(source, context);
  return { content: value => modification(value), ready() { context.YoastSEO.app = app; onReady(); }, count: () => registrations };
}
test('homepage analysis substitutes the latest editor content literally', () => {
  const b = bridge({ html: '<main>before SLOT after</main>', slot: 'SLOT', emptyEditorFallback: '<p>fallback</p>' });
  assert.equal(b.content('<p>$& current</p>'), '<main>before <p>$& current</p> after</main>');
  assert.equal(b.content(''), '<main>before <p>fallback</p> after</main>');
});
test('template-only pages use rendered body, not obsolete editor copy', () => {
  const b = bridge({ html: '<p>Rendered press archive</p>', slot: null });
  assert.equal(b.content('<p>Old archive</p>'), '<p>Rendered press archive</p>');
});
test('late Yoast initialization registers once', () => {
  const b = bridge({ html: '<p>Rendered</p>', slot: null }, false);
  assert.equal(b.count(), 0); b.ready(); b.ready(); assert.equal(b.count(), 1);
});
