(() => {
  const toggle = document.querySelector(".menu-toggle");
  toggle?.addEventListener("click", () => {
    const open = toggle.getAttribute("aria-expanded") !== "true";
    toggle.setAttribute("aria-expanded", String(open));
    document
      .getElementById("site-navigation")
      .classList.toggle("is-open", open);
  });
  document.addEventListener("keydown", (e) => {
    const search = document.querySelector(".header-search");
    if (e.key === "Escape" && search?.open) {
      search.open = false;
      search.querySelector("summary").focus();
    }
    if (
      e.key === "Escape" &&
      toggle?.getAttribute("aria-expanded") === "true"
    ) {
      toggle.click();
      toggle.focus();
    }
  });
  const search = document.querySelector(".header-search");
  search?.addEventListener("toggle", () => {
    if (search.open) search.querySelector('input[type="search"]').focus();
  });
  document.querySelectorAll("[data-youtube]").forEach((button) => {
    button.addEventListener("click", () => {
      const id = button.dataset.youtube;
      if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
      const frame = document.createElement("iframe");
      frame.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1`;
      frame.title = button.getAttribute("aria-label");
      frame.allow = "autoplay; fullscreen; picture-in-picture";
      frame.allowFullscreen = true;
      frame.referrerPolicy = "strict-origin-when-cross-origin";
      button.replaceWith(frame);
      frame.focus();
    });
  });
})();

(() => {
  const popup = document.querySelector(".newsletter-popup");
  const key = "valon_newsletter_dismissed_until";
  let suppressed = false;
  const remember = (days = 30) => {
    suppressed = true;
    try { localStorage.setItem(key, String(Date.now() + days * 86400000)); } catch {}
  };
  document.addEventListener("submit", (event) => {
    // Native Mailchimp navigation is an intention, not proof of subscription.
    if (event.target.matches('.newsletter-form[data-hosted="true"]') && event.target.checkValidity()) remember();
  });
  document.addEventListener("newsletter:submitted", () => remember());
  if (!popup || typeof popup.showModal !== "function") return;
  let previousFocus;
  document.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-newsletter-open]");
    if (!trigger) return;
    event.preventDefault();
    previousFocus = trigger;
    popup.showModal();
    remember();
    document.documentElement.classList.add("newsletter-popup-open");
  });
  const isSuppressed = () => {
    try { return suppressed || Number(localStorage.getItem(key)) > Date.now(); }
    catch { return suppressed; }
  };
  popup.querySelector("[data-newsletter-close]").addEventListener("click", () => popup.close());
  popup.addEventListener("close", () => {
    remember();
    document.documentElement.classList.remove("newsletter-popup-open");
    if (previousFocus?.isConnected) previousFocus.focus({ preventScroll: true });
  });
  popup.addEventListener("click", (event) => {
    const rect = popup.getBoundingClientRect();
    if (event.target === popup && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) popup.close();
  });
  let elapsed = false;
  const maybeOpen = () => {
    if (!elapsed || popup.open || isSuppressed() || document.visibilityState !== "visible") return;
    const distance = document.documentElement.scrollHeight - innerHeight;
    if (distance > 0 && scrollY < distance * 0.35) return;
    // Do not interrupt typing, an open menu, search or another modal.
    if (document.activeElement?.matches("input, textarea, select, [contenteditable='true']") || document.querySelector('dialog[open], .header-search[open], .menu-toggle[aria-expanded="true"]')) return;
    previousFocus = document.activeElement;
    popup.showModal();
    remember();
    document.documentElement.classList.add("newsletter-popup-open");
  };
  setTimeout(() => { elapsed = true; maybeOpen(); }, 20000);
  window.addEventListener("scroll", maybeOpen, { passive: true });
  document.addEventListener("visibilitychange", maybeOpen);
})();
