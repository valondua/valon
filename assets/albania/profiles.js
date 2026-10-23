(() => {
  'use strict';
  const profiles = window.albaniaProfilesData || {};
  const base = new URL('images/', document.currentScript.src).href;
  const aliases = {'Edi Rama':'rama','Albanian government':'gov','Belinda Balluku':'balluku','Al-Khayyat investors':'qatar','Sazan':'sazan','Sazan / Zvërnec':'zvernec','Jewish Museum':'museum'};
  const el = (tag, text, className) => {const n=document.createElement(tag); if(text)n.textContent=text; if(className)n.className=className; return n;};
  function lookup(name) {return profiles[name] || profiles[aliases[name]] || Object.values(profiles).find(p=>p.name===name);}
  function imageFor(p) {const img=el('img'); img.src=base+p.photo.file; img.alt=p.photo.caption; img.width=160; img.height=160; img.loading='lazy'; img.addEventListener('error',()=>{const fallback=el('span','Photo unavailable','profile-placeholder'); img.replaceWith(fallback);},{once:true}); return img;}
  function header(name, fallbackRole) {
    const p=lookup(name)||{name,role:fallbackRole||'Research subject. Read the dated findings and limitations below for its role in this investigation.'};
    const section=el('div',null,'profile-heading'); const copy=el('div');
    if(p.photo){const figure=el('figure');figure.append(imageFor(p));const caption=el('figcaption',p.photo.caption+' ');const credit=el('a',p.photo.credit);credit.href=p.photo.url;const license=el('a',p.photo.license);license.href=p.photo.licenseUrl;caption.append(credit,document.createTextNode(' · '),license,document.createTextNode(' · resized; display cropped.'));figure.append(caption);section.append(figure);}
    else {const badge=el('span',p.name.split(/\s+/).slice(0,2).map(w=>w[0]).join(''),'profile-placeholder');badge.setAttribute('aria-hidden','true');section.append(badge);}
    copy.append(el('p','Role in this investigation','kicker'),el('h3',p.name),el('p',p.role));section.append(copy);return section;
  }
  function node(name) {const p=lookup(name);return p?.photo?{shape:'circularImage',image:base+p.photo.file,size:34,borderWidth:3,color:{border:'#426de5',background:'#fff'},font:{color:'#162c43',background:'#fff',size:14},shapeProperties:{useBorderWithImage:true}}:{};}
  window.albaniaProfiles={lookup,header,node,imageFor,el};
})();
