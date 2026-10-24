/* Native links and server-rendered content work without this enhancement. */
(() => {
 'use strict';
 const config=JSON.parse(document.getElementById('dossier-route-map')?.textContent||'{}');
 const norm=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
 const t=s=>(window.albaniaTranslate||String)(s);
 function destination(hash){const key=config.anchors?.[hash.replace(/^#/,'')];return key&&key!==config.current?config.routes[key]+hash:null;}
 function anchor(){
  const url=destination(location.hash);if(url){location.replace(url);return;}
  document.querySelectorAll('.albania-language a').forEach(a=>{const url=new URL(a.href);url.hash=location.hash;a.href=url.href;});
  const target=document.getElementById(location.hash.slice(1));if(!target)return;
  target.closest('details')?.setAttribute('open','');target.querySelector('details')?.setAttribute('open','');
  target.tabIndex=-1;requestAnimationFrame(()=>{target.focus({preventScroll:true});target.scrollIntoView({block:'start'});});
 }
 function links(root=document){root.querySelectorAll('a[href^="#"]').forEach(a=>{const url=destination(a.getAttribute('href'));if(url)a.href=url;});}
 links();new MutationObserver(records=>{for(const r of records)for(const n of r.addedNodes)if(n.nodeType===1){if(n.matches('a[href^="#"]')){const url=destination(n.getAttribute('href'));if(url)n.href=url;}links(n);}}).observe(document.body,{childList:true,subtree:true});
 window.addEventListener('hashchange',anchor);anchor();
 const search=document.getElementById('sourcefilter');
 if(search){const cards=[...document.querySelectorAll('.document-item')];function filter(){const recordSearch=document.getElementById('research-search');if(recordSearch){recordSearch.value=search.value;recordSearch.dispatchEvent(new Event('input',{bubbles:true}));}document.getElementById('document-results').open=!!search.value.trim();const terms=norm(search.value).split(/\s+/).filter(Boolean);let count=0;cards.forEach(c=>{c.hidden=!terms.every(s=>norm(c.textContent).includes(s));if(!c.hidden)count++;});document.getElementById('document-count').textContent=count+' / '+cards.length;document.getElementById('document-empty').hidden=count!==0;}search.addEventListener('input',filter);filter();}
 const feed=document.getElementById('feed-search');
 if(feed){const cards=[...document.querySelectorAll('.feed-card')],type=document.getElementById('feed-type'),language=document.getElementById('feed-language'),more=document.getElementById('feed-more'),month=document.getElementById('feed-month');let limit=4;
 function filter(){const terms=norm(feed.value).split(/\s+/).filter(Boolean);let count=0;cards.forEach(c=>{const match=(!month.value||(month.value==='unknown'?!c.dataset.date:c.dataset.date.startsWith(month.value)))&&(!type.value||c.dataset.type===type.value)&&(!language.value||c.dataset.language===language.value)&&terms.every(s=>norm(c.textContent).includes(s));c.hidden=!match||count>=limit;if(match)count++;});document.getElementById('feed-count').textContent=Math.min(count,limit)+' / '+count;document.getElementById('feed-empty').hidden=count!==0;more.hidden=count<=limit;}
 [feed,type,language,month].forEach(e=>e.addEventListener('input',()=>{limit=4;filter();}));more.addEventListener('click',()=>{limit+=4;filter();});filter();}
 // Only explicitly reviewed player IDs can create an external frame; no third-party request before a click.
 const players={rumble:new Set(['v7ey0zo']),youtube:new Set(['x2nXzaHIBSI','iqHe3iky0QM'])};
 document.querySelectorAll('.speech-player').forEach(box=>{
  const {provider,videoId}=box.dataset;if(!players[provider]?.has(videoId))return;
  const card=box.closest('.speech-card'),button=box.querySelector('.speech-load'),close=card.querySelector('.speech-close'),status=card.querySelector('.speech-status');
  box.hidden=false;
  button.addEventListener('click',()=>{
   if(box.querySelector('iframe'))return;
   const frame=document.createElement('iframe');
   frame.src=provider==='youtube'?'https://www.youtube-nocookie.com/embed/'+videoId+'?autoplay=0&rel=0':'https://rumble.com/embed/'+videoId+'/?pub=4fye6d&autoplay=0';
   frame.title=card.querySelector('h4').textContent+' — '+t('Load video');
   frame.allow='encrypted-media; fullscreen; picture-in-picture';frame.allowFullscreen=true;frame.referrerPolicy='strict-origin-when-cross-origin';
   button.hidden=true;box.appendChild(frame);close.hidden=false;
   status.textContent=t('Player requested. If it does not load, open the original video below.');frame.focus();
  });
  close.addEventListener('click',()=>{box.querySelector('iframe')?.remove();button.hidden=false;close.hidden=true;status.textContent=t('Player closed.');button.focus();});
 });
})();
