/* Native anchors remain usable without JavaScript. */
(() => {
  const header = document.querySelector('body > header');
  const target = document.getElementById('demands');
  if (!target) return;
  function measureHeader() {
    if (header) document.documentElement.style.setProperty('--dossier-anchor-offset', `${Math.ceil(header.getBoundingClientRect().bottom) + 20}px`);
  }
  measureHeader();
  if (window.ResizeObserver && header) new ResizeObserver(measureHeader).observe(header);
  window.addEventListener('resize', measureHeader);
  function revealDemands() {
    if (!['#demands', '#jewish-history-projects', '#christopher-hyland'].includes(location.hash)) return;
    const target = document.getElementById(location.hash.slice(1));
    if (!target) return;
    measureHeader();
    target.focus({preventScroll: true});
    target.scrollIntoView({block: 'start', behavior: 'instant'});
  }
  // Both languages use identical record, event and section IDs.
  function preserveAnchor() {
    document.querySelectorAll('.albania-language a').forEach(link => {
      const url = new URL(link.href, location.href);
      url.hash = location.hash;
      link.href = url.href;
    });
  }
  window.addEventListener('hashchange', () => { preserveAnchor(); revealDemands(); });
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href="#demands"], a[href="#jewish-history-projects"], a[href="#christopher-hyland"]');
    if (link && location.hash === link.getAttribute('href')) { event.preventDefault(); revealDemands(); }
  });
  preserveAnchor();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(revealDemands), {once: true});
  else requestAnimationFrame(revealDemands);
})();
