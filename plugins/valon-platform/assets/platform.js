(() => {
  "use strict";
  const cfg = window.valonPlatform;
  if (!cfg) return;
  const messages = {
    en: {
      sending: "Sending…",
      retry: "Please try again.",
      offline: "Couldn’t connect. Please try again.",
      archive: "Archive video",
      close: "Close",
      social: "Social post",
      notice: "This post loads from its social platform.",
      original: "View original ↗",
      unavailable: "The post could not load.",
    },
    sq: {
      sending: "Tue dërgu…",
      retry: "Provo prapë.",
      offline: "S’u lidhëm dot. Provo prapë.",
      archive: "Video e arkivit",
      close: "Mbyll",
      social: "Postim social",
      notice: "Ky postim ngarkohet nga platforma sociale.",
      original: "Shiko origjinalin ↗",
      unavailable: "Postimi s’mund të ngarkohet.",
    },
    de: {
      sending: "Wird gesendet…",
      retry: "Bitte versuche es nochmals.",
      offline: "Keine Verbindung. Bitte versuche es nochmals.",
      archive: "Video aus dem Archiv",
      close: "Schliessen",
      social: "Social-Media-Beitrag",
      notice: "Dieser Beitrag wird von der jeweiligen Plattform geladen.",
      original: "Original ansehen ↗",
      unavailable: "Der Beitrag konnte nicht geladen werden.",
    },
  };
  const pageLanguage = document.documentElement.lang.split("-")[0];
  const message = (key, language = pageLanguage) =>
    (messages[language] || messages.en)[key];
  const consentListeners = new Set();
  let analyticsConsent = false;
  const hasStatisticsConsent = () => {
    try {
      const prefix = window.consent_api?.cookie_prefix;
      // wp_has_consent alone also permits unset consent in opt-out regions.
      // Custom events require the visitor's explicit statistics choice.
      return (
        typeof prefix === "string" &&
        prefix.length > 0 &&
        typeof window.consent_api_get_cookie === "function" &&
        typeof window.wp_has_consent === "function" &&
        window.consent_api_get_cookie(prefix + "_statistics") === "allow" &&
        window.wp_has_consent("statistics") === true
      );
    } catch {
      return false;
    }
  };
  const syncConsent = () => {
    const next = hasStatisticsConsent();
    window.valonAnalyticsConsent = next;
    if (next !== analyticsConsent) {
      analyticsConsent = next;
      consentListeners.forEach((listener) => listener(next));
    }
    return next;
  };
  document.addEventListener("wp_listen_for_consent_change", syncConsent);
  document.addEventListener("wp_consent_type_defined", syncConsent);
  window.addEventListener("load", syncConsent);
  syncConsent();
  const track = (name, params = {}) => {
    // Never queue pre-consent interactions or install another Google tag.
    if (!syncConsent() || typeof window.gtag !== "function") return false;
    try {
      window.gtag("event", name, params);
      return true;
    } catch {
      return false;
    }
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
  const rememberSource = (consented) => {
    try {
      if (!consented) sessionStorage.removeItem("valon_source");
      else if (allowed.includes(source))
        sessionStorage.setItem("valon_source", source);
    } catch {}
  };
  consentListeners.add(rememberSource);
  rememberSource(analyticsConsent);
  const newsletterSource = () => {
    // An explicit source in this URL needs no browser storage.
    if (allowed.includes(source)) return source;
    if (syncConsent()) {
      try {
        const stored = sessionStorage.getItem("valon_source");
        if (allowed.includes(stored)) return stored;
      } catch {}
    }
    return "website";
  };
  const newsletterPlacement = (element) => {
    const placement =
      element?.dataset.placement ||
      element?.closest(".newsletter-hosted")?.dataset.placement;
    if (["hero", "footer", "landing", "newsletter", "inline"].includes(placement))
      return placement;
    if (element?.closest(".hero-newsletter")) return "hero";
    if (element?.closest(".newsletter-band")) return "footer";
    if (element?.closest(".letter-signup")) return "landing";
    if (element?.closest(".page-signup")) return "newsletter";
    return "inline";
  };
  const newsletterLanguage = (language) =>
    ["en", "sq", "de"].includes(language) ? language : "en";
  document.querySelectorAll(".newsletter-form").forEach((form) => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const button = form.querySelector("button"),
        status = form.querySelector(".form-status");
      const data = new FormData(form);
      const stored = newsletterSource();
      const payload = {
        email: data.get("email"),
        language: data.get("language"),
        consent: data.get("consent") === "1",
        website: data.get("website"),
        source: stored,
      };
      button.disabled = true;
      status.textContent = message("sending", payload.language);
      try {
        const r = await fetch(cfg.subscribe, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload),
        });
        const result = await r.json();
        status.textContent =
          result.message || message("retry", payload.language);
        if (r.ok) {
          if (!payload.website)
            track("newsletter_submit", {
              language: newsletterLanguage(payload.language),
              placement: newsletterPlacement(form),
              source: stored,
            });
          form.reset();
        }
      } catch {
        status.textContent = message("offline", payload.language);
      } finally {
        button.disabled = false;
      }
    });
  });
  let dialog;
  document.addEventListener("click", async (e) => {
    const signupLink = e.target.closest("a[href]");
    if (signupLink) {
      let url;
      try {
        url = new URL(signupLink.href, location.href);
      } catch {}
      // This owned form link is a signup intention, never a subscription.
      if (
        url?.protocol === "https:" &&
        url.hostname === "eepurl.com" &&
        url.pathname.replace(/\/$/, "") === "/h-inUL"
      ) {
        track("newsletter_signup_click", {
          language: newsletterLanguage(pageLanguage),
          placement: newsletterPlacement(signupLink),
          source: newsletterSource(),
        });
      }
    }
    const legacy = e.target.closest("[data-legacy-embed]");
    if (legacy) {
      const iframe = document.createElement("iframe");
      iframe.src = legacy.dataset.legacyEmbed;
      iframe.title = message("archive");
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
      dialog.setAttribute("aria-label", message("social"));
      document.body.append(dialog);
    }
    dialog.replaceChildren();
    const close = document.createElement("button");
    close.className = "embed-close";
    close.textContent = "×";
    close.setAttribute("aria-label", message("close"));
    close.onclick = () => dialog.close();
    dialog.append(close);
    const notice = document.createElement("p");
    notice.className = "embed-notice";
    notice.textContent = message("notice");
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
      link.textContent = message("original");
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
      mount.textContent = message("unavailable");
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
      last = performance.now(),
      wasReading = false,
      timer;
    const articlePosition = () => {
      const rect = article.getBoundingClientRect();
      return {
        visible:
          document.visibilityState === "visible" &&
          rect.height > 0 &&
          rect.bottom > 0 &&
          rect.top < window.innerHeight,
        reached:
          rect.height > 0 &&
          window.innerHeight >= rect.top + rect.height * 0.75,
      };
    };
    const resetReading = () => {
      visibleMs = 0;
      last = performance.now();
      wasReading = analyticsConsent && articlePosition().visible;
    };
    consentListeners.add(resetReading);
    resetReading();
    const check = () => {
      const consented = syncConsent();
      const now = performance.now();
      if (consented && wasReading) visibleMs += Math.max(0, now - last);
      last = now;
      const position = articlePosition();
      wasReading = consented && position.visible;
      if (
        !sent &&
        wasReading &&
        visibleMs >= 30000 &&
        position.reached
      ) {
        sent = track("article_engaged", {
          article_id: article.dataset.article,
          language: newsletterLanguage(pageLanguage),
        });
      }
    };
    const start = () => {
      syncConsent();
      resetReading();
      if (timer === undefined) timer = setInterval(check, 1000);
    };
    start();
    window.addEventListener("pagehide", () => {
      clearInterval(timer);
      timer = undefined;
      resetReading();
    });
    window.addEventListener("pageshow", (event) => {
      if (event.persisted) start();
    });
    document.addEventListener("visibilitychange", check);
    window.addEventListener("scroll", check, { passive: true });
  }
  if (document.querySelector("[data-page-error]"))
    track("page_not_found", { page_path: location.pathname });
})();
