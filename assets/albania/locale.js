(() => {
 'use strict';const dictionary=window.albaniaTranslations||{};
 function t(raw){if(typeof raw!=='string')return raw;const key=raw.trim().replace(/\s+/g,' ');if(dictionary[key])return raw.replace(raw.trim(),dictionary[key]);
  const totals=key.match(/^(\d+) of (\d+) (research entries|events)$/);if(totals)return totals[1]+' nga '+totals[2]+' '+(totals[3]==='events'?'ngjarje':'zëra kërkimi');
  const source=key.match(/^Source (\d+) ↗$/);if(source)return 'Burimi '+source[1]+' ↗';
  const count=key.match(/^(\d+) (connections in this graph|linked evidence records)$/);if(count)return count[1]+' '+t(count[2]);
  if(key.startsWith('View '))return 'Shiko '+t(key.slice(5));
  if(key.endsWith(' →'))return t(key.slice(0,-2))+' →';
  for(const separator of [' · ',' → ',' — ','\n'])if(raw.includes(separator))return raw.split(separator).map(x=>t(x)).join(separator);
  return raw;
 }
 window.albaniaTranslate=t;
 function translate(root){if(root.nodeType===3){if(root.parentElement?.closest('script,style,textarea,[translate="no"],.albania-language'))return;const next=t(root.nodeValue);if(next!==root.nodeValue)root.nodeValue=next;return;}if(root.nodeType!==1&&root.nodeType!==9)return;
  if(root.nodeType===1){if(root.tagName==='OPTION'&&!root.hasAttribute('value'))root.setAttribute('value',root.textContent);for(const a of ['alt','title','aria-label','placeholder'])if(root.hasAttribute(a)){const old=root.getAttribute(a),next=t(old);if(old!==next)root.setAttribute(a,next);}}
  for(const node of root.childNodes)translate(node);
 }
 const observer=new MutationObserver(records=>{for(const record of records){if(record.type==='characterData')translate(record.target);else if(record.type==='attributes')translate(record.target);else record.addedNodes.forEach(translate);}});
 observer.observe(document.body,{childList:true,subtree:true,characterData:true,attributes:true,attributeFilter:['alt','title','aria-label','placeholder']});translate(document.body);
 // Canvas labels are not DOM text. Translate only their display labels, never IDs or filter values.
 function localizeNetwork(){if(!window.vis?.Network)return;const Original=vis.Network;function Network(container,data,options){for(const key of ['nodes','edges']){const list=Array.isArray(data[key])?data[key]:data[key]?.get?.();if(list){const translated=list.map(x=>({...x,label:t(x.label),title:t(x.title)}));if(Array.isArray(data[key]))data[key]=translated;else data[key].update(translated);}}return new Original(container,data,options);}Network.prototype=Original.prototype;vis.Network=Network;}
 if(window.vis)localizeNetwork();else document.addEventListener('DOMContentLoaded',localizeNetwork,{once:true});
})();
