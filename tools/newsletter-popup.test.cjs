const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const code = fs.readFileSync(require('node:path').join(__dirname, '../js/site.js'), 'utf8');
function harness({ stored = 0, blocked = false, typing = false } = {}) {
  const events = {}, popupEvents = {}, timers = [];
  let shows = 0;
  const popup = {
    open: false,
    showModal() { this.open = true; shows++; },
    close() { this.open = false; popupEvents.close(); },
    addEventListener(n, f) { popupEvents[n] = f; },
    querySelector() { return { addEventListener() {} }; },
  };
  const context = {
    Date, innerHeight: 800, scrollY: 0,
    setTimeout(f) { timers.push(f); },
    localStorage: { getItem() { if (blocked) throw Error(); return stored; }, setItem(k,v) { if (blocked) throw Error(); stored = v; } },
    document: {
      visibilityState: 'visible',
      activeElement: { matches() { return typing; } },
      documentElement: { scrollHeight: 2800, classList: { add() {}, remove() {} } },
      querySelector(s) { return s === '.newsletter-popup' ? popup : null; },
      querySelectorAll() { return []; },
      addEventListener(n,f) { events[n] = f; },
    },
    window: { addEventListener(n,f) { events[n] = f; } },
  };
  vm.runInNewContext(code, context);
  return { popup, events, context, elapsed: () => timers.forEach(f=>f()), shows: () => shows };
}
test('waits for both time and reading depth; dismissal prevents reopening', () => {
  const h = harness(); h.elapsed(); assert.equal(h.shows(), 0);
  h.context.scrollY = 700; h.events.scroll(); assert.equal(h.shows(), 1);
  h.popup.close(); h.events.scroll(); assert.equal(h.shows(), 1);
});
test('honours saved suppression and avoids typing or hidden pages', () => {
  for (const options of [{stored: Date.now()+86400000}, {typing:true}]) {
    const h=harness(options); h.context.scrollY=1000; h.elapsed(); assert.equal(h.shows(),0);
  }
  const h=harness(); h.context.scrollY=1000; h.context.document.visibilityState='hidden'; h.elapsed(); assert.equal(h.shows(),0);
  h.context.document.visibilityState='visible'; h.events.visibilitychange(); assert.equal(h.shows(),1);
});
test('blocked browser storage still suppresses repeat invitations on the page', () => {
  const h=harness({blocked:true}); h.context.scrollY=1000; h.elapsed(); h.popup.close(); h.events.scroll(); assert.equal(h.shows(),1);
});
test('accepted signup suppresses a later popup', () => {
  const h=harness(); h.events['newsletter:submitted'](); h.context.scrollY=1000; h.elapsed(); assert.equal(h.shows(),0);
});
