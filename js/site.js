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
})();
