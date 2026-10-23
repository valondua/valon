const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const code = fs.readFileSync(require('node:path').join(__dirname, '../js/site.js'), 'utf8');
function harness({ stored = 0, blocked = false, typing = false, pointer = true } = {}) {
  const events = {}, popupEvents = {}, timers = [];
  let shows = 0;
  const form = { dataset: { placement: "popup" } };
  const popup = {
    open: false,
    showModal() { this.open = true; shows++; },
    close() { this.open = false; popupEvents.close(); },
    addEventListener(n, f) { popupEvents[n] = f; },
    querySelector(s) { return s === ".newsletter-form" ? form : { addEventListener() {} }; },
  };
  const context = {
    CustomEvent: class { constructor(type, options) { this.type=type; this.detail=options.detail; } },
    Date, innerHeight: 800, scrollY: 0,
    setTimeout(f) { timers.push(f); },
    localStorage: { getItem() { if (blocked) throw Error(); return stored; }, setItem(k,v) { if (blocked) throw Error(); stored = v; } },
    document: {
      dispatchEvent(e) { events[e.type]?.(e); },
      visibilityState: 'visible',
      activeElement: { matches() { return typing; } },
      documentElement: { scrollHeight: 2800, classList: { add() {}, remove() {} } },
      querySelector(s) { return s === '.newsletter-popup' ? popup : null; },
      querySelectorAll() { return []; },
      addEventListener(n,f) { events[n] = f; },
    },
    window: { matchMedia() { return { matches: pointer }; }, addEventListener(n,f) { events[n] = f; } },
  };
  vm.runInNewContext(code, context);
  return { popup, form, events, context, elapsed: () => timers.forEach(f=>f()), shows: () => shows };
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

test('top-edge exit opens after the delay without requiring reading depth', () => {
  const h = harness();
  const exit = { relatedTarget: null, clientY: 0 };
  h.events.mouseout(exit); assert.equal(h.shows(), 0);
  h.elapsed(); h.events.mouseout(exit); assert.equal(h.shows(), 1);
  h.popup.close(); h.events.mouseout(exit); assert.equal(h.shows(), 1);
});
test('exit ignores touch devices, in-page transitions and side exits', () => {
  const h = harness(); h.elapsed();
  h.events.mouseout({ relatedTarget: {}, clientY: 0 });
  h.events.mouseout({ relatedTarget: null, clientY: 100 });
  assert.equal(h.shows(), 0);
  for (const options of [{pointer: false}, {typing: true}, {stored: Date.now()+86400000}]) {
    const blocked = harness(options); blocked.elapsed();
    blocked.events.mouseout({ relatedTarget: null, clientY: 0 });
    assert.equal(blocked.shows(), 0);
  }
});

test('opening trigger persists and later exit cannot relabel timed popup', () => {
  const h=harness(); h.context.scrollY=700; h.elapsed();
  assert.equal(h.form.dataset.placement,'popup_timed');
  h.events.mouseout({relatedTarget:null,clientY:0});
  assert.equal(h.form.dataset.placement,'popup_timed');
  const e=harness(); e.elapsed(); e.events.mouseout({relatedTarget:null,clientY:0});
  assert.equal(e.form.dataset.placement,'popup_exit');
});
test('manual reopening gets its own attribution', () => {
  const h=harness(); h.elapsed(); h.events.mouseout({relatedTarget:null,clientY:0}); h.popup.close();
  h.events.click({target:{closest:()=>({})},preventDefault(){}});
  assert.equal(h.form.dataset.placement,'popup_manual');
});

test('exactly one opening notification per actual popup display',()=>{
  const h=harness(); const seen=[];
  h.events['newsletter:popup-open']=e=>seen.push(e.detail.form.dataset.placement);
  h.elapsed(); h.events.mouseout({relatedTarget:null,clientY:0});
  h.events.mouseout({relatedTarget:null,clientY:0});
  assert.deepEqual(seen,['popup_exit']);
});
