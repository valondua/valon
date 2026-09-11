(() => {
  "use strict";
  const cfg = window.valonPlatform;
  if (!cfg) return;
  const track = (name, params = {}) => {
    // The consent manager may dispatch this event after consent. No new GA tag is installed.
    if (
      window.valonAnalyticsConsent === true &&
      typeof window.gtag === "function"
    )
      window.gtag("event", name, params);
  };
  const allowed = [
    "tiktok",
    "instagram",
    "facebook",
    "linkedin",
    "x",
    "newsletter",
  ];
  const source = new URL(location.href).searchParams.get("utm_source");
  if (allowed.includes(source)) {
    try {
      sessionStorage.setItem("valon_source", source);
    } catch {}
  }
  document.querySelectorAll(".newsletter-form").forEach((form) => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const button = form.querySelector("button"),
        status = form.querySelector(".form-status");
      const data = new FormData(form);
      let stored = "website";
      try {
        stored = sessionStorage.getItem("valon_source") || "website";
        if (!allowed.includes(stored)) stored = "website";
      } catch {}
      const payload = {
        email: data.get("email"),
        language: data.get("language"),
        consent: data.get("consent") === "1",
        website: data.get("website"),
        source: stored,
      };
      button.disabled = true;
      status.textContent =
        payload.language === "sq" ? "Tue dërgu…" : "Sending…";
      try {
        const r = await fetch(cfg.subscribe, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload),
        });
        const result = await r.json();
        status.textContent =
          result.message ||
          (payload.language === "sq" ? "Provo prapë." : "Please try again.");
        if (r.ok) {
          track("newsletter_submit", {
            language: payload.language,
            placement: form.dataset.placement,
            source: stored,
          });
          form.reset();
        }
      } catch {
        status.textContent =
          payload.language === "sq"
            ? "S’u lidhëm dot. Provo prapë."
            : "Couldn’t connect. Please try again.";
      } finally {
        button.disabled = false;
      }
    });
  });
  let dialog;
  document.addEventListener("click", async (e) => {
    const legacy = e.target.closest("[data-legacy-embed]");
    if (legacy) {
      const iframe = document.createElement("iframe");
      iframe.src = legacy.dataset.legacyEmbed;
      iframe.title = document.documentElement.lang.startsWith("sq")
        ? "Video e arkivit"
        : "Archive video";
      iframe.allow = "fullscreen";
      iframe.referrerPolicy = "strict-origin-when-cross-origin";
      legacy.replaceWith(iframe);
      return;
    }
    const sourceLink = e.target.closest("[data-social-source]");
    if (sourceLink)
      track("social_source_click", {
        platform: sourceLink.dataset.socialSource,
      });
    const button = e.target.closest("[data-social-id]");
    if (!button) return;
    if (!dialog) {
      dialog = document.createElement("dialog");
      dialog.className = "embed-dialog";
      dialog.setAttribute("aria-label", "Social post");
      document.body.append(dialog);
    }
    dialog.replaceChildren();
    const close = document.createElement("button");
    close.className = "embed-close";
    close.textContent = "×";
    close.setAttribute(
      "aria-label",
      document.documentElement.lang.startsWith("sq") ? "Mbyll" : "Close",
    );
    close.onclick = () => dialog.close();
    dialog.append(close);
    const notice = document.createElement("p");
    notice.className = "embed-notice";
    notice.textContent = document.documentElement.lang.startsWith("sq")
      ? "Ky postim ngarkohet nga platforma sociale."
      : "This post loads from its social platform.";
    dialog.append(notice);
    const mount = document.createElement("div");
    mount.className = "embed-mount";
    dialog.append(mount);
    dialog.showModal();
    const fallback = button
      .closest(".social-card")
      ?.querySelector("[data-social-source]")
      ?.cloneNode(true);
    dialog.addEventListener(
      "close",
      () => {
        dialog.replaceChildren();
        button.focus();
      },
      { once: true },
    );
    try {
      const r = await fetch(cfg.embed + button.dataset.socialId);
      if (!r.ok) throw new Error("unavailable");
      const data = await r.json();
      if (!dialog.open) return;
      const link = document.createElement("a");
      link.href = data.source;
      link.textContent = document.documentElement.lang.startsWith("sq")
        ? "Shiko origjinalin ↗"
        : "View original ↗";
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      if (data.iframe) {
        const iframe = document.createElement("iframe");
        iframe.src = data.iframe;
        iframe.title = data.platform + " post";
        iframe.allow = "fullscreen";
        iframe.referrerPolicy = "strict-origin-when-cross-origin";
        mount.append(iframe);
      } else if (data.platform === "x") {
        const block = document.createElement("blockquote");
        block.className = "twitter-tweet";
        block.append(link.cloneNode(true));
        mount.append(block);
        if (!document.querySelector("[data-x-widget]")) {
          const script = document.createElement("script");
          script.src = "https://platform.twitter.com/widgets.js";
          script.async = true;
          script.dataset.xWidget = "true";
          document.body.append(script);
        } else window.twttr?.widgets?.load(mount);
      }
      mount.append(link);
      track("social_post_open", { platform: data.platform });
    } catch {
      mount.textContent = document.documentElement.lang.startsWith("sq")
        ? "Postimi s’mund të ngarkohet."
        : "The post could not load.";
      if (fallback) mount.append(fallback);
    }
  });
  document.querySelectorAll(".social-cover img").forEach((img) =>
    img.addEventListener("error", () => {
      img.style.visibility = "hidden";
    }),
  );
  const article = document.querySelector("[data-article]");
  if (article) {
    let sent = false,
      visibleMs = 0,
      last = Date.now();
    const check = () => {
      const now = Date.now();
      if (document.visibilityState === "visible") visibleMs += now - last;
      last = now;
      if (
        !sent &&
        visibleMs >= 30000 &&
        window.scrollY + window.innerHeight >=
          article.offsetTop + article.offsetHeight * 0.75
      ) {
        sent = true;
        track("article_engaged", {
          article_id: article.dataset.article,
          language: document.documentElement.lang,
        });
      }
    };
    const timer = setInterval(check, 1000);
    window.addEventListener("pagehide", () => clearInterval(timer), {
      once: true,
    });
    document.addEventListener("visibilitychange", () => {
      last = Date.now();
    });
    window.addEventListener("scroll", check, { passive: true });
  }
  if (document.querySelector("[data-page-error]"))
    track("page_not_found", { page_path: location.pathname });
})();
