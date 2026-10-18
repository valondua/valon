(() => {
'use strict';
const root=document.querySelector('.aa-library'); if(!root) return;
const form=root.querySelector('.aa-filters'),query=root.querySelector('#aa-query'),family=root.querySelector('#aa-family'),genre=root.querySelector('#aa-genre'),status=root.querySelector('#aa-status');
const cards=[...root.querySelectorAll('.aa-card')],groups=[...root.querySelectorAll('.aa-family')];
const fold=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase();
const texts=cards.map(c=>fold(c.textContent));
function update(){const q=fold(query.value.trim());let count=0;cards.forEach((c,i)=>{const show=(!q||texts[i].includes(q))&&(!family.value||c.dataset.family===family.value)&&(!genre.value||c.dataset.genres.split(' ').includes(genre.value))&&(!status.value||c.dataset.inspection===status.value);c.hidden=!show;if(show)count++;});groups.forEach(g=>{const all=[...g.querySelectorAll('.aa-card')],n=all.filter(c=>!c.hidden).length;g.hidden=n===0;g.querySelector('.aa-section-heading span').textContent=`${n} nga ${all.length} ${all.length===1?'burim':'burime'}`;});root.querySelector('.aa-result').textContent=`${count} nga ${cards.length} burime.`;root.querySelector('.aa-empty').hidden=count!==0;}
form.hidden=false;form.addEventListener('submit',e=>e.preventDefault());form.addEventListener('input',update);form.addEventListener('change',update);form.addEventListener('reset',()=>setTimeout(update,0));
root.querySelectorAll('.aa-start a').forEach(a=>a.addEventListener('click',()=>{form.reset();query.value='';family.value='';genre.value='';status.value='';update();}));
})();
