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
    if (
      e.key === "Escape" &&
      toggle?.getAttribute("aria-expanded") === "true"
    ) {
      toggle.click();
      toggle.focus();
    }
  });
})();
