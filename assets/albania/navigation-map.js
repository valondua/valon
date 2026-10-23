/* Independent controls: navigation remains usable if another widget fails. */
(() => {
 'use strict';
 const t = value => (window.albaniaTranslate || String)(value);
 const nav = document.getElementById('sectionNavigation');
 const header = document.querySelector('.albania-investigation > header');
 const jump = document.getElementById('sectionJump');
 function measure() {
  document.body.style.setProperty('--masthead-height', header.getBoundingClientRect().height + 'px');
  document.body.style.setProperty('--section-nav-height', nav.getBoundingClientRect().height + 'px');
 }
 if (nav && header && jump) {
  jump.hidden = false; nav.classList.add('enhanced');
  jump.addEventListener('change', () => { if (jump.value) location.href = jump.value; jump.value = ''; });
  if (window.ResizeObserver) { const observer = new ResizeObserver(measure); observer.observe(header); observer.observe(nav); }
  window.addEventListener('resize', measure); measure();
 }
 const container = document.getElementById('map'), button = document.getElementById('loadMap'), status = document.getElementById('mapStatus');
 if (!container || !button || !status) return;
 const views = {all:[[40.85,19.55],8],coast:[[40.51,19.36],11],tirana:[[41.3275,19.82],13]};
 const sites = [['Tirana · Parliament',41.3275,19.8187,'Parliamentary votes and executive decisions'],['Tirana · student protests',41.329,19.822,'Approximate central protest area'],['Sazan Island',40.503,19.282,'Proposed luxury development on state land'],['Zvërnec / Narta',40.515,19.42,'Wetland and coastal development controversy'],['Vlora · Jewish Museum',40.466,19.49,'Approximate historical-center project location']];
 let map, tiles, timer, current = 'all', errors = 0;
 function failed(partial = false) {
  clearTimeout(timer); status.textContent = t(partial ? 'Some map tiles could not load. Retry or use the location links below.' : 'The map could not load. Retry or use the location links below.');
  button.textContent = t('Retry map'); button.hidden = false; button.disabled = false;
 }
 function loading() { clearTimeout(timer); errors = 0; status.textContent = t('Loading map…'); timer = setTimeout(() => failed(), 15000); }
 function load(view = current) {
  current = view;
  try {
   if (!window.L) throw new Error('Map library unavailable');
   container.hidden = false;
   if (!map) {
    map = L.map(container, {scrollWheelZoom:false});
    tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'});
    tiles.on('loading', loading);
    tiles.on('tileerror', () => { errors++; });
    tiles.on('load', () => { clearTimeout(timer); if (errors) failed(true); else { status.textContent = t('Map loaded. Select a marker to read about the location.'); button.hidden = true; } });
    tiles.addTo(map);
    sites.forEach(site => { const popup=document.createElement('div'), name=document.createElement('strong'), description=document.createElement('p'); name.textContent=t(site[0]);description.textContent=t(site[3]);popup.append(name,description);L.marker([site[1],site[2]],{title:t(site[0]),alt:t(site[0])}).addTo(map).bindPopup(popup); });
   }
   loading(); map.invalidateSize(); map.setView(...views[current]); tiles.redraw();
   button.hidden = true;
   document.querySelectorAll('[data-map-view]').forEach(b => {b.setAttribute('aria-pressed',String(b.dataset.mapView===current));b.classList.toggle('active',b.dataset.mapView===current);});
  } catch (_) { if (map) map.remove(); map=null; container.hidden=true; failed(); }
 }
 button.addEventListener('click', () => { if(map){map.remove();map=null;} load(); });
 document.querySelectorAll('[data-map-view]').forEach(b => b.addEventListener('click', () => load(b.dataset.mapView)));
})();
