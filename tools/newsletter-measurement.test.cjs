const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const script = fs.readFileSync(require('node:path').join(__dirname,'../plugins/valon-platform/assets/newsletter-measurement.js'),'utf8');
function harness(choice='allow', pending=null) {
  const calls=[],events=[],storage=new Map(),listeners={}; let submits=0, id='12345.67890';
  if(pending) storage.set('valon_newsletter_confirmation',JSON.stringify(pending));
  const form={dataset:{hosted:'true',lang:'sq',placement:'hero'},checkValidity:()=>true,addEventListener:(n,f)=>listeners['form:'+n]=f};
  const window={valonPlatform:{newsletterImpression:'/impression',newsletterIntent:'/intent',newsletterRevoke:'/revoke',newsletterMeasurementId:'G-9264D19BZD'},consent_api:{cookie_prefix:'wp'},consent_api_get_cookie:()=>choice,wp_has_consent:()=>choice==='allow',gtag:(...a)=>{events.push(a);if(a[0]==='get')a[3](a[2]==='client_id'?id:String(Math.floor(Date.now()/1000)));},addEventListener:(n,f)=>listeners[n]=f};
  let response={token:'a'.repeat(64),expires:Date.now()/1000+3600};
  const context={window,document:{visibilityState:'visible',querySelectorAll:()=>[form],addEventListener:(n,f)=>listeners[n]=f},localStorage:{getItem:k=>storage.get(k),setItem:(k,v)=>storage.set(k,v),removeItem:k=>storage.delete(k)},sessionStorage:{getItem:()=>null},location:{href:'https://www.valonasani.com/?utm_source=facebook'},URL,AbortController,Date,JSON,setTimeout,clearTimeout,FormData:class{get(k){return k==='EMAIL'?'reader@example.invalid':null;}},HTMLFormElement:{prototype:{submit(){submits++;}}},fetch:async(url,o)=>{calls.push({url,body:JSON.parse(o.body)});return {ok:true,json:async()=>url==='/impression'?{id:'b'.repeat(64)}:response};}};
  vm.runInNewContext(script,context);
  return {calls,events,storage,listeners,form,get submits(){return submits},set response(v){response=v},set id(v){id=v},revoke(){choice='deny'}};
}
const submit=h=>h.listeners['form:submit']({preventDefault(){}});
test('no consent: no analytics identifiers, tracking requests or storage; signup continues',async()=>{const h=harness('deny');await h.listeners['form:submit']({preventDefault(){throw Error('blocked without consent')}});assert.equal(h.calls.length,0);assert.equal(h.events.length,0);assert.equal(h.storage.size,0)});
test('consented signup captures existing client and session for server reporting, not email in GA',async()=>{const h=harness();await submit(h);assert.equal(h.submits,1);assert.equal(h.calls[0].body.client_id,'12345.67890');assert.equal(h.calls[0].body.source,'facebook');assert.equal(h.events.filter(e=>e[0]==='event').length,0);assert(!JSON.stringify([...h.storage]).includes('@'));assert(!JSON.stringify(h.events).includes('@'));});
test('a browser return never emits or claims the server-owned conversion',async()=>{const h=harness('allow',{token:'a'.repeat(64),expires:Date.now()/1000+3600});await h.listeners.pageshow();assert.equal(h.events.length,0);assert.equal(h.calls.length,0)});
test('withdrawal revokes the pending server receipt before clearing storage',async()=>{const h=harness();await submit(h);h.revoke();await h.listeners.wp_listen_for_consent_change();assert.equal(h.calls[1].url,'/revoke');assert.equal(h.storage.size,0)});
test('unknown startup consent does not erase a receipt',async()=>{const h=harness(null,{token:'a'.repeat(64),expires:Date.now()/1000+3600});await h.listeners.pageshow();assert.equal(h.calls.length,0);assert.equal(h.storage.size,1)});
test('failed recording never blocks subscription',async()=>{const h=harness();h.response=null;await submit(h);assert.equal(h.submits,1);assert.equal(h.storage.size,0)});
test('missing GA identifier is not replaced with an invented client',async()=>{const h=harness();h.id=null;await submit(h);assert.equal(h.calls[0].body.client_id,'');assert.equal(h.submits,1)});
test('failed withdrawal retains receipt for a later retry',async()=>{const h=harness();await submit(h);h.response=null;h.revoke();await h.listeners.wp_listen_for_consent_change();assert.equal(h.storage.size,1);h.response={ok:true};await h.listeners.pageshow();assert.equal(h.storage.size,0)});

test('popup placement is read at submit time after opening',async()=>{
  for (const placement of ['popup_exit','popup_timed','popup_manual']) {
    const h=harness(); h.form.dataset.placement=placement; await submit(h);
    assert.equal(h.calls[0].body.placement,placement);
  }
});

test('consented view is recorded and linked to later intent',async()=>{
 const h=harness(); h.form.dataset.placement='popup_exit';
 h.listeners['newsletter:popup-open']({detail:{form:h.form}}); await submit(h);
 assert.equal(h.calls[0].url,'/impression');
 assert.equal(h.calls[0].body.placement,'popup_exit');
 assert.equal(h.calls[1].body.impression_id,'b'.repeat(64));
});
test('denied or unknown consent records no popup views',async()=>{
 for (const choice of ['deny',null]) {
  const h=harness(choice);h.form.dataset.placement='popup_timed';
  h.listeners['newsletter:popup-open']({detail:{form:h.form}});
  await Promise.resolve();assert.equal(h.calls.length,0);
 }
});
test('withdrawal during identifier lookup prevents impression and signup measurement',async()=>{
 const h=harness();h.form.dataset.placement='popup_timed';
 h.listeners['newsletter:popup-open']({detail:{form:h.form}});h.revoke();
 await Promise.resolve();await Promise.resolve();assert.equal(h.calls.length,0);
});
