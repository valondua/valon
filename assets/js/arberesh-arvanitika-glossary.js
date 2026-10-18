(() => {
  'use strict';
  const root = document.querySelector('.ag-glossary');
  if (!root) return;
  const form = root.querySelector('.ag-controls');
  const q = root.querySelector('#ag-q');
  const tradition = root.querySelector('#ag-tradition');
  const source = root.querySelector('#ag-source');
  const count = root.querySelector('#ag-count');
  const empty = root.querySelector('#ag-empty');
  const fold = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('sq').replace(/\s+/g, ' ').trim();
  const cards = [...root.querySelectorAll('#ag-results .ag-card')].map(el => ({el, text: fold(el.dataset.search)}));
  function filter() {
    const terms = fold(q.value).split(' ').filter(Boolean);
    let visible = 0;
    for (const card of cards) {
      const match = (!tradition.value || card.el.dataset.tradition === tradition.value) && (!source.value || card.el.dataset.source === source.value) && terms.every(term => card.text.includes(term));
      card.el.hidden = !match;
      if (match) visible++;
    }
    count.textContent = `${visible} nga ${cards.length} dëshmi · kontroll pamor nga AI`;
    empty.hidden = visible !== 0;
  }
  form.addEventListener('submit', event => event.preventDefault());
  form.addEventListener('input', filter);
  form.addEventListener('change', filter);
  form.addEventListener('reset', () => { setTimeout(() => {filter(); q.focus();}, 0); });
  form.hidden = false;
  filter();
})();
