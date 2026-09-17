#!/usr/bin/env node
// Source-backed integration replay, not a browser or Google-delivery test.
// Downloads only public source. Google gtag.js is deliberately never loaded.
const assert = require("node:assert/strict");
const crypto = require("node:crypto");
const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");

const pageURL = "https://www.valonasani.com/";
const liveBridge = process.argv.includes("--live-bridge");
const requireCMP = process.argv.includes("--require-cmp");
const sha256 = (value) => crypto.createHash("sha256").update(value).digest("hex");
const clean = (value) => JSON.parse(JSON.stringify(value));
async function readPublic(url) {
  assert.equal(new URL(url).origin, new URL(pageURL).origin);
  const response = await fetch(url, { signal: AbortSignal.timeout(15000) });
  assert.equal(response.ok, true, `${url}: HTTP ${response.status}`);
  return response.text();
}
function scripts(html) {
  return [...html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)].map((match) => {
    const attributes = Object.fromEntries([...match[1].matchAll(/([\w-]+)=["']([^"']*)["']/g)]
      .map((attribute) => [attribute[1], attribute[2].replaceAll("&amp;", "&")]));
    return { ...attributes, code: match[2] };
  });
}
function eventSurface() {
  const listeners = new Map();
  return {
    addEventListener(name, fn) {
      if (!listeners.has(name)) listeners.set(name, []);
      listeners.get(name).push(fn);
    },
    dispatchEvent(event) {
      for (const fn of listeners.get(event.type) || []) fn(event);
      return true;
    },
  };
}
function harness(source, stored = {}, overrideType) {
  const cookies = new Map(Object.entries(stored));
  const storage = new Map();
  const document = Object.assign(eventSurface(), {
    documentElement: { lang: "en-US" },
    querySelector: () => null,
    querySelectorAll: () => [],
  });
  Object.defineProperty(document, "cookie", {
    get: () => [...cookies].map(([key, value]) => `${key}=${value}`).join("; "),
    set(value) {
      const item = value.split(";")[0];
      const offset = item.indexOf("=");
      cookies.set(item.slice(0, offset), item.slice(offset + 1));
    },
  });
  const context = Object.assign(eventSurface(), {
    document,
    location: new URL(`${pageURL}?utm_source=linkedin`),
    URL,
    console,
    CustomEvent: class { constructor(type, options = {}) { this.type = type; this.detail = options.detail; } },
    sessionStorage: {
      getItem: (key) => storage.get(key) ?? null,
      setItem: (key, value) => storage.set(key, value),
      removeItem: (key) => storage.delete(key),
    },
    performance: { now: () => 0 },
    setTimeout: () => 0,
    clearTimeout() {},
    setInterval: () => 0,
    clearInterval() {},
  });
  context.window = context;
  context.self = context;
  vm.createContext(context, { codeGeneration: { strings: false, wasm: false } });
  const run = (entry) => vm.runInContext(entry.code, context, {
    filename: entry.src || entry.id,
    timeout: 5000,
  });
  // Preserve the relevant scripts' observed document order. The async Google
  // loader is omitted; the original inline gtag function records the queue.
  for (const entry of source.replay) run(entry);
  if (overrideType) context.wp_consent_type = overrideType;
  document.dispatchEvent({ type: "DOMContentLoaded" });
  context.dispatchEvent({ type: "load" });
  if (source.cmpBridge) run({ id: "extracted-public-cmplz_wp_set_consent", code: source.cmpBridge });
  return {
    context, cookies, storage,
    queue: () => clean(Array.from(context.dataLayer || [], (value) => Array.from(value))),
    events() { return this.queue().filter((value) => value[0] === "event"); },
    updates() { return this.queue().filter((value) => value[0] === "consent" && value[1] === "update"); },
    choose(category, value) {
      // Replay the real public CMP→API adapter where available. This does not
      // simulate the banner UI or pretend a human clicked its buttons.
      const fn = source.cmpBridge ? context.cmplz_wp_set_consent : context.wp_set_consent;
      fn(category, value);
    },
    click() {
      const container = { dataset: { placement: "hero" } };
      const link = { href: "https://eepurl.com/h-inUL", dataset: {},
        closest: (selector) => selector === ".newsletter-hosted" ? container : null };
      document.dispatchEvent({ type: "click", target: {
        closest: (selector) => selector === "a[href]" ? link : null,
      } });
    },
  };
}

async function main() {
  const html = await readPublic(pageURL);
  const allScripts = scripts(html);
  const ids = new Set([
    "google_gtagjs-js-consent-mode-data-layer", "google_gtagjs-js-after",
    "valon-platform-js-extra", "valon-platform-js", "googlesitekit-consent-mode-js",
    "wp-consent-api-js-extra", "wp-consent-api-js",
  ]);
  const replay = allScripts.filter((entry) => ids.has(entry.id));
  assert.equal(replay.length, ids.size, "Required public script set changed; inspect the page before adjusting this harness");
  await Promise.all(replay.map(async (entry) => {
    if (entry.src) entry.code = await readPublic(entry.src);
    entry.publicSha256 = sha256(entry.code);
    if (entry.id === "valon-platform-js" && !liveBridge) {
      entry.code = fs.readFileSync(path.join(__dirname, "../plugins/valon-platform/assets/platform.js"), "utf8");
      entry.localOverride = true;
    }
  }));
  const cmp = allScripts.find((entry) => entry.src?.includes("/complianz-gdpr/") && /complianz(?:\.min)?\.js/.test(entry.src));
  let cmpBridge;
  let cmpReceipt;
  if (cmp) {
    const cmpCode = await readPublic(cmp.src);
    // Only the small published adapter runs, so no fake banner DOM is required.
    const match = cmpCode.match(/function cmplz_wp_set_consent\([^)]*\)\s*\{[^{}]*(?:\{[^{}]*\}[^{}]*)?\}/);
    assert.ok(match, "Public Complianz adapter changed; inspect before adjusting extraction");
    assert.match(match[0], /wp_set_consent/);
    cmpBridge = match[0];
    cmpReceipt = { url: cmp.src, sha256: sha256(cmpCode), replayed: "cmplz_wp_set_consent only" };
  }
  if (requireCMP) assert.ok(cmpBridge, "No public Complianz adapter found; CMP integration cannot yet be replayed");
  const source = { replay, cmpBridge };
  const checks = [];
  const check = (name, fn) => { fn(); checks.push(name); };
  check("public default command denies analytics and advertising worldwide", () => {
    const h = harness(source);
    const defaults = h.queue().find((value) => value[0] === "consent" && value[1] === "default");
    assert.ok(defaults);
    for (const key of ["analytics_storage", "ad_storage", "ad_user_data", "ad_personalization"])
      assert.equal(defaults[2][key], "denied");
    assert.equal(defaults[2].region, undefined);
  });
  check("no-choice and anonymous consent emit no custom analytics", () => {
    const h = harness(source);
    h.click();
    h.choose("statistics-anonymous", "allow");
    h.click();
    assert.equal(h.events().length, 0);
    assert.equal(h.context.valonAnalyticsConsent, false);
    assert.equal(h.storage.size, 0);
  });
  check("statistics acceptance updates Google consent and enables one bounded click", () => {
    const h = harness(source);
    h.choose("statistics", "allow");
    assert.equal(h.context.wp_has_consent("statistics"), true);
    assert.equal(h.context.valonAnalyticsConsent, true);
    assert.deepEqual(h.updates().at(-1), ["consent", "update", { analytics_storage: "granted" }]);
    assert.equal(h.storage.get("valon_source"), "linkedin");
    h.click();
    assert.deepEqual(h.events(), [["event", "newsletter_signup_click", {
      language: "en", placement: "hero", source: "linkedin",
    }]]);
  });
  check("revoke updates Google denied, blocks future clicks, and removes attribution", () => {
    const h = harness(source);
    h.choose("statistics", "allow");
    h.click();
    h.choose("statistics", "deny");
    assert.deepEqual(h.updates().at(-1), ["consent", "update", { analytics_storage: "denied" }]);
    h.click();
    assert.equal(h.events().length, 1);
    assert.equal(h.context.valonAnalyticsConsent, false);
    assert.equal(h.storage.size, 0);
  });
  check("real WP API rejects invalid choice values", () => {
    const h = harness(source);
    h.choose("statistics", "granted");
    h.click();
    assert.equal(h.events().length, 0);
    assert.equal(h.cookies.has("wp_consent_statistics"), false);
  });
  check("persisted statistics allow initializes both Site Kit and bridge", () => {
    const h = harness(source, { wp_consent_statistics: "allow" });
    assert.equal(h.context.valonAnalyticsConsent, true);
    assert.equal(h.updates().at(-1)[2].analytics_storage, "granted");
    h.click();
    assert.equal(h.events().length, 1);
  });
  check("opt-out API default never counts as explicit bridge consent", () => {
    const h = harness(source, {}, "optout");
    assert.equal(h.context.wp_has_consent("statistics"), true);
    h.click();
    assert.equal(h.events().length, 0);
    assert.equal(h.context.valonAnalyticsConsent, false);
  });
  console.log(JSON.stringify({
    result: "PASS", checkedAt: new Date().toISOString(), pageURL,
    bridge: liveBridge ? "exact public deployed source" : "local candidate against public integration scripts",
    checks,
    source: replay.map((entry) => ({ id: entry.id, url: entry.src || pageURL,
      sha256: sha256(entry.code), publicSha256: entry.publicSha256, localOverride: entry.localOverride || false })),
    complianz: cmpReceipt || "Not in current public HTML; tested real WP Consent API calls directly",
    limitations: ["Node VM with mocked DOM/cookies; not a live browser test", "Google gtag.js transport never loaded; no production analytics events sent", "CMP banner, regional inference, actual browser cookies and network delivery require separate verification"],
  }, null, 2));
}
main().catch((error) => { console.error(error.stack); process.exitCode = 1; });
