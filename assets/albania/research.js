/* Progressive enhancement: all evidence and source links exist in the server HTML. */
(() => {
  'use strict';
  const form = document.getElementById('research-filters');
  if (!form) return;
  const search = document.getElementById('research-search');
  const topic = document.getElementById('research-topic');
  const status = document.getElementById('research-status');
  const count = document.getElementById('research-count');
  const empty = document.getElementById('research-empty');
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  const records = [...document.querySelectorAll('.research-record')].map(element => ({element, text: normalize(element.textContent)}));
  function filter() {
    const terms = normalize(search.value).trim().split(/\s+/).filter(Boolean);
    let visible = 0;
    records.forEach(({element, text}) => {
      const match = (!topic.value || element.dataset.topic === topic.value) &&
        (!status.value || element.dataset.status === status.value) && terms.every(term => text.includes(term));
      element.hidden = !match;
      if (match) visible++;
    });
    count.textContent = `${visible} of ${records.length} research entries`;
    empty.hidden = visible !== 0;
  }
  form.addEventListener('submit', event => event.preventDefault());
  form.addEventListener('input', filter);
  form.addEventListener('change', filter);
  form.addEventListener('reset', () => { setTimeout(filter, 0); });
  function revealHash() {
    const target = records.find(({element}) => '#' + element.id === window.location.hash);
    if (!target) return;
    search.value = topic.value = status.value = '';
    filter();
    target.element.querySelector('details').open = true;
    target.element.setAttribute('tabindex', '-1');
    target.element.focus({preventScroll: true});
    target.element.scrollIntoView({block: 'start', behavior: 'auto'});
  }
  window.addEventListener('hashchange', revealHash);
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#r"]');
    if (link && link.hash === location.hash) { event.preventDefault(); revealHash(); }
  });
  form.hidden = false;
  filter();
  // Wait for the other widgets and translated text before positioning a direct URL.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(revealHash), {once: true});
  } else requestAnimationFrame(revealHash);
})();
