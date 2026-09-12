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
