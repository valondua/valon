const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const read=p=>fs.readFileSync(path.join(__dirname,'..',p),'utf8');
test('initial graph construction receives translated canvas labels and preserves stable IDs',()=>{
 const dictionary=JSON.parse(read('assets/albania/sq.json'));let received;
 const vis={Network:class{constructor(container,data){received=data;}}};
 const body={nodeType:1,tagName:'BODY',hasAttribute:()=>false,childNodes:[]};
 const context={window:{albaniaTranslations:dictionary,vis},vis,document:{body,addEventListener:()=>{throw Error('Wrapper deferred until after graph construction');}},MutationObserver:class{observe(){}}};
 vm.runInNewContext(read('assets/albania/locale.js'),context);
 new vis.Network({}, {nodes:[{id:'node-en',label:'Official account'}],edges:[{id:'edge-en',label:'Documented record'}]},{});
 assert.equal(received.nodes[0].label,dictionary['Official account']);assert.equal(received.edges[0].label,dictionary['Documented record']);assert.equal(received.nodes[0].id,'node-en');assert.equal(received.edges[0].id,'edge-en');
 assert.match(read('includes/albania-language.php'),/wp_script_is\('albania-network','registered'\).*\$locale_deps\[\]='albania-network'/);
});
test('visible source query, canonical records and document results stay synchronized on reset and record reveal',()=>{
 const elements={},windowEvents={};let form;
 function element(id,text=''){const handlers={};const e={id,textContent:text,value:'',hidden:false,dataset:{},open:false,tabIndex:0,handlers,addEventListener:(name,fn)=>(handlers[name]??=[]).push(fn),dispatchEvent:event=>{for(const f of handlers[event.type]||[])f(event);if(id==='research-search')for(const f of form.handlers[event.type]||[])f(event);},querySelector:()=>e.details,querySelectorAll:()=>[],closest:()=>null,setAttribute:()=>{},focus:()=>{},scrollIntoView:()=>{}};elements[id]=e;return e;}
 form=element('research-filters');for(const id of ['research-search','research-topic','research-status','research-count','research-empty','sourcefilter','document-count','document-empty','document-results'])element(id);
 const records=[element('r27','Museum evidence'),element('r01','Sazan evidence')];for(const r of records)r.details={open:false,setAttribute(){this.open=true;}};const docs=[element('doc1','Elton archive'),element('doc2','Museum document')];
 element('dossier-route-map','{"current":"sources","routes":{},"anchors":{}}');
 const document={body:{},readyState:'complete',getElementById:id=>elements[id]||null,querySelectorAll:s=>s==='.research-record'?records:s==='.document-item'?docs:[],addEventListener:()=>{}};
 const location={hash:'',href:'https://example.test/albania/sources/'};const context={document,location,URL,Event,requestAnimationFrame:fn=>fn(),setTimeout:fn=>fn(),MutationObserver:class{observe(){}},window:{location,addEventListener:(name,fn)=>(windowEvents[name]??=[]).push(fn)}};
 vm.runInNewContext(read('assets/albania/dossier.js'),context);vm.runInNewContext(read('assets/albania/research.js'),context);
 function search(q){elements.sourcefilter.value=q;elements.sourcefilter.dispatchEvent(new Event('input'));}
 search('Elton');assert.equal(records.filter(r=>!r.hidden).length,0);assert.equal(docs.filter(r=>!r.hidden).length,1);assert.equal(elements['document-results'].open,true);
 // Native form reset first restores its own fields, then the asynchronous reset handler synchronizes the external query.
 elements['research-search'].value='';form.dispatchEvent(new Event('reset'));
 assert.equal(elements.sourcefilter.value,'');assert.equal(records.filter(r=>!r.hidden).length,2);assert.equal(docs.filter(r=>!r.hidden).length,2);assert.equal(elements['document-results'].open,false);
 search('Elton');location.hash='#r27';for(const handler of windowEvents.hashchange)handler();
 assert.equal(elements.sourcefilter.value,'');assert.equal(elements['research-search'].value,'');assert.equal(records.filter(r=>!r.hidden).length,2);assert.equal(docs.filter(r=>!r.hidden).length,2);assert.equal(elements.r27.details.open,true);
});
