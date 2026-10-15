const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const script = fs.readFileSync('plugins/valon-platform/assets/newsletter-measurement.js','utf8');
function harness(allow=false, pending=null) {
  const calls=[],events=[],storage=new Map(),listeners={}; let submits=0;
  if(pending) storage.set('valon_newsletter_confirmation',JSON.stringify(pending));
  const form={dataset:{hosted:'true',lang:'sq',placement:'hero'},checkValidity:()=>true,addEventListener:(n,f)=>listeners['form:'+n]=f};
  const window={valonPlatform:{newsletterIntent:'/intent',newsletterConfirmation:'/status'},consent_api:{cookie_prefix:'wp'},consent_api_get_cookie:()=>allow?'allow':'deny',wp_has_consent:()=>allow,gtag:(...a)=>events.push(a),addEventListener:(n,f)=>listeners[n]=f};
  let response={token:'a'.repeat(64),expires:Date.now()/1000+3600};
  const context={window,document:{visibilityState:'visible',querySelectorAll:()=>[form],addEventListener:(n,f)=>listeners[n]=f},localStorage:{getItem:k=>storage.get(k),setItem:(k,v)=>storage.set(k,v),removeItem:k=>storage.delete(k)},sessionStorage:{getItem:()=>null},location:{href:'https://www.valonasani.com/?utm_source=facebook'},URL,AbortController,Date,JSON,setTimeout,clearTimeout,setInterval:()=>0,FormData:class{get(k){return k==='EMAIL'?'reader@example.invalid':null;}},HTMLFormElement:{prototype:{submit(){submits++;}}},fetch:async(url,o)=>{calls.push({url,body:JSON.parse(o.body)});return {ok:true,json:async()=>response};}};
  vm.runInNewContext(script,context);
  return {calls,events,storage,listeners,form,get submits(){return submits},set response(v){response=v},revoke(){allow=false}};
}
const flush=()=>new Promise(r=>setImmediate(r));
test('without consent no tracking requests or storage; normal form continues',async()=>{const h=harness();await h.listeners['form:submit']({preventDefault(){throw Error('blocked without consent')}});assert.equal(h.calls.length,0);assert.equal(h.storage.size,0)});
test('hosted form records bounded intent then continues; email never goes to GA',async()=>{const h=harness(true);let prevented=false;await h.listeners['form:submit']({preventDefault(){prevented=true}});assert(prevented);assert.equal(h.submits,1);assert.equal(h.calls[0].body.source,'facebook');assert.equal(h.events.length,0);assert(!JSON.stringify([...h.storage]).includes('@'));});
test('only a server-confirmed result emits a confirmed event',async()=>{const h=harness(true,{token:'a'.repeat(64),expires:Date.now()/1000+3600});await flush();assert.equal(h.events.length,0);h.response={confirmed:true,parameters:{language:'sq',source:'facebook',placement:'hero'}};await h.listeners.pageshow();assert.equal(h.events[0][1],'newsletter_confirmed');assert(!JSON.stringify(h.events).includes('@'));assert.equal(h.storage.size,0);await h.listeners.pageshow();assert.equal(h.events.length,1)});
test('withdrawal clears pending browser attribution',async()=>{const h=harness(true);await h.listeners['form:submit']({preventDefault(){}});h.revoke();await h.listeners.wp_listen_for_consent_change();assert.equal(h.storage.size,0);assert.equal(h.events.length,0)});
test('expired tokens never query a confirmation',async()=>{const h=harness(true,{token:'a'.repeat(64),expires:1});await flush();assert.equal(h.calls.length,0);assert.equal(h.storage.size,0)});
test('failure of intent recording never blocks hosted subscription',async()=>{const h=harness(true);h.response=null;await h.listeners['form:submit']({preventDefault(){}});assert.equal(h.submits,1);assert.equal(h.storage.size,0)});
