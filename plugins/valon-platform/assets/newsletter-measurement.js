(() => {
  "use strict";
  const cfg = window.valonPlatform;
  if (!cfg?.newsletterIntent || !cfg?.newsletterConfirmation) return;
  const storageKey = "valon_newsletter_confirmation";
  const consented = () => {
    try {
      const prefix = window.consent_api?.cookie_prefix;
      return typeof prefix === "string" && !!prefix &&
        window.consent_api_get_cookie?.(prefix + "_statistics") === "allow" &&
        window.wp_has_consent?.("statistics") === true;
    } catch { return false; }
  };
  const clear = () => { try { localStorage.removeItem(storageKey); } catch {} };
  const request = async (url, body) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 2000);
    try {
      const response = await fetch(url, {
        method: "POST", credentials: "same-origin", cache: "no-store",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body), signal: controller.signal,
      });
      return response.ok ? await response.json() : null;
    } finally { clearTimeout(timeout); }
  };
  const sources = ["tiktok", "instagram", "facebook", "linkedin", "x", "newsletter"];
  document.querySelectorAll(".newsletter-form").forEach((form) => {
    let submitting = false;
    form.addEventListener("submit", async (event) => {
      if (!consented() || !form.checkValidity()) return;
      const hosted = form.dataset.hosted === "true";
      const data = new FormData(form);
      if (data.get("website") || data.get("b_c37037a826fe84a19f2ad26d1_293f2ca94d")) return;
      if (hosted) event.preventDefault();
      if (submitting) return;
      submitting = true;
      let source = new URL(location.href).searchParams.get("utm_source");
      try { if (!sources.includes(source)) source = sessionStorage.getItem("valon_source"); } catch {}
      try {
        const result = await request(cfg.newsletterIntent, {
          email: data.get(hosted ? "EMAIL" : "email"), statistics_consent: true,
          language: data.get("language") || form.dataset.lang || "en",
          placement: form.dataset.placement || "inline",
          source: sources.includes(source) ? source : "website",
        });
        if (consented() && result?.token && /^[a-f0-9]{64}$/.test(result.token)) {
          try { localStorage.setItem(storageKey, JSON.stringify(result)); } catch {}
        }
      } catch { /* Measurement must never prevent subscription. */ }
      finally {
        submitting = false;
        if (hosted) HTMLFormElement.prototype.submit.call(form);
      }
    });
  });
  let checking = false;
  const check = async () => {
    if (!consented()) { clear(); return; }
    if (checking || document.visibilityState !== "visible" || typeof window.gtag !== "function") return;
    let pending;
    try { pending = JSON.parse(localStorage.getItem(storageKey) || "null"); } catch { clear(); return; }
    if (!pending) return;
    if (!/^[a-f0-9]{64}$/.test(pending.token || "") || !(pending.expires > Date.now() / 1000)) { clear(); return; }
    checking = true;
    try {
      const result = await request(cfg.newsletterConfirmation, { token: pending.token });
      if (result?.confirmed && consented()) {
        // The server atomically claims this signed confirmation only once.
        const p = result.parameters || {};
        window.gtag("event", "newsletter_confirmed", {
          language: ["en", "sq", "de"].includes(p.language) ? p.language : "en",
          source: [...sources, "website"].includes(p.source) ? p.source : "website",
          placement: ["hero", "footer", "landing", "newsletter", "inline", "popup"].includes(p.placement) ? p.placement : "inline",
          method: "mailchimp_double_opt_in",
        });
        clear();
      } else if (result?.finished) clear();
    } catch { /* The signed server ledger remains the source of truth. */ }
    finally { checking = false; }
  };
  document.addEventListener("wp_listen_for_consent_change", check);
  document.addEventListener("wp_consent_type_defined", check);
  document.addEventListener("visibilitychange", check);
  window.addEventListener("load", check);
  window.addEventListener("pageshow", check);
  // This timer makes no request without consent and an unexpired pending token.
  setInterval(check, 30000);
  check();
})();
