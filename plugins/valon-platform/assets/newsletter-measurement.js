(() => {
  "use strict";
  const cfg = window.valonPlatform;
  if (!cfg?.newsletterIntent || !cfg?.newsletterRevoke) return;
  const storageKey = "valon_newsletter_confirmation";
  const choice = () => {
    try {
      const prefix = window.consent_api?.cookie_prefix;
      return prefix ? window.consent_api_get_cookie?.(prefix + "_statistics") : null;
    } catch { return null; }
  };
  const consented = () => choice() === "allow" && window.wp_has_consent?.("statistics") === true;
  const clear = () => { try { localStorage.removeItem(storageKey); } catch {} };
  const request = async (url, body) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 2000);
    try {
      const response = await fetch(url, {method:"POST", credentials:"same-origin", cache:"no-store", keepalive:true,
        headers:{"Content-Type":"application/json"}, body:JSON.stringify(body), signal:controller.signal});
      return response.ok ? await response.json() : null;
    } finally { clearTimeout(timeout); }
  };
  const revoke = async (receipt) => {
    if (!receipt?.token || !/^[a-f0-9]{64}$/.test(receipt.token)) return;
    try { if (await request(cfg.newsletterRevoke, {token:receipt.token})) clear(); } catch {}
  };
  const checkWithdrawal = async () => {
    // Unknown/startup consent is not withdrawal. Never send analytics from this script.
    if (choice() !== "deny") return;
    try { await revoke(JSON.parse(localStorage.getItem(storageKey) || "null")); } catch {}
  };
  const gaValue = (field) => new Promise((resolve) => {
    if (!consented() || typeof window.gtag !== "function") return resolve(null);
    const timer = setTimeout(() => resolve(null), 600);
    window.gtag("get", cfg.newsletterMeasurementId, field, value => { clearTimeout(timer); resolve(value); });
  });
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
        const [clientId, sessionId] = await Promise.all([gaValue("client_id"), gaValue("session_id")]);
        // Never invent a GA identifier when tagging is blocked or consent is withdrawn.
        if (!consented()) return;
        const result = await request(cfg.newsletterIntent, {
          email:data.get(hosted ? "EMAIL" : "email"), statistics_consent:true,
          client_id:typeof clientId === "string" ? clientId : "",
          session_id:sessionId ? String(sessionId) : "",
          language:data.get("language") || form.dataset.lang || "en", placement:form.dataset.placement || "inline",
          source:sources.includes(source) ? source : "website",
        });
        if (result?.token && /^[a-f0-9]{64}$/.test(result.token)) {
          try { localStorage.setItem(storageKey, JSON.stringify(result)); } catch {}
          if (!consented()) await revoke(result);
        }
      } catch { /* Measurement must never prevent subscription. */ }
      finally { submitting = false; if (hosted) HTMLFormElement.prototype.submit.call(form); }
    });
  });
  document.addEventListener("wp_listen_for_consent_change", checkWithdrawal);
  document.addEventListener("wp_consent_type_defined", checkWithdrawal);
  window.addEventListener("pageshow", checkWithdrawal);
  checkWithdrawal();
})();
