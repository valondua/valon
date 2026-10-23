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
  const visitorKey = "valon_popup_visitor";
  let visitor = null;
  let impressions = new WeakMap();
  let consentEpoch = 0;
  const visitorId = () => {
    if (!consented()) return "";
    if (visitor?.expires > Date.now()) return visitor.id;
    try { visitor = JSON.parse(localStorage.getItem(visitorKey) || "null"); } catch {}
    if (!visitor || !/^[a-f0-9]{32}$/.test(visitor.id) || !Number.isFinite(visitor.expires) || visitor.expires <= Date.now()) {
      const bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      visitor = {id:Array.from(bytes, b => b.toString(16).padStart(2,"0")).join(""), expires:Date.now()+30*86400000};
      try { localStorage.setItem(visitorKey, JSON.stringify(visitor)); } catch {}
    }
    return visitor.id;
  };
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
    consentEpoch++;
    visitor = null;
    impressions = new WeakMap();
    try { localStorage.removeItem(visitorKey); } catch {}
    try { await revoke(JSON.parse(localStorage.getItem(storageKey) || "null")); } catch {}
  };
  const gaValue = (field) => new Promise((resolve) => {
    if (!consented() || typeof window.gtag !== "function") return resolve(null);
    const timer = setTimeout(() => resolve(null), 600);
    window.gtag("get", cfg.newsletterMeasurementId, field, value => { clearTimeout(timer); resolve(value); });
  });
  document.addEventListener("newsletter:popup-open", (event) => {
    const form = event.detail?.form;
    if (!form) return;
    impressions.delete(form);
    const placement = form.dataset.placement;
    if (!cfg.newsletterImpression || !consented() || document.visibilityState !== "visible" ||
        !["popup_exit", "popup_timed", "popup_manual"].includes(placement)) return;
    // Snapshot the actual opening. Never reconstruct past views after late consent.
    const epoch = consentEpoch;
    const pending = (async () => {
      try {
        const id = visitorId();
        if (!id || !consented()) return null;
        const result = await request(cfg.newsletterImpression, {statistics_consent:true, visitor_id:id, placement});
        return consented() && epoch === consentEpoch && result ? {...result, visitor_id:id} : null;
      } catch { return null; }
    })();
    impressions.set(form, pending);
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
        const impression = await impressions.get(form);
        if (!consented()) return;
        const result = await request(cfg.newsletterIntent, {
          impression_id:impression?.id || "",
          visitor_id:impression?.visitor_id || "",
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
