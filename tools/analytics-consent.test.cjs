const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const vm = require("node:vm");

const code = fs.readFileSync(
  path.join(__dirname, "../plugins/valon-platform/assets/platform.js"),
  "utf8",
);

// Run the deployed browser script with controlled time, consent and responses.
// No external services, cookies, subscriber data or dependencies are needed.
function harness(options = {}) {
  let now = 0;
  let choice = options.choice ?? "deny";
  let top = 0;
  let fetchResponse = async () => ({ ok: true, json: async () => ({}) });
  let timerId = 0;
  const timers = new Map();
  const storage = new Map();
  const events = [];
  const requests = [];
  const surface = () => {
    const listeners = new Map();
    return {
      addEventListener(name, handler) {
        if (!listeners.has(name)) listeners.set(name, []);
        listeners.get(name).push(handler);
      },
      dispatch(name, event = {}) {
        return Promise.all((listeners.get(name) || []).map((fn) => fn(event)));
      },
    };
  };
  const button = { disabled: false };
  const status = { textContent: "" };
  const form = Object.assign(surface(), {
    dataset: { placement: "landing" },
    data: {
      email: "fixture@example.invalid",
      language: "sq",
      consent: "1",
      website: "",
    },
    valid: true,
    resets: 0,
    reportValidity() { return this.valid; },
    querySelector(selector) { return selector === "button" ? button : status; },
    closest() { return null; },
    reset() { this.resets += 1; },
  });
  const article = {
    dataset: { article: "123" },
    getBoundingClientRect() {
      return { top, bottom: top + 1000, height: 1000 };
    },
  };
  const document = Object.assign(surface(), {
    documentElement: { lang: "en-US" },
    visibilityState: "visible",
    querySelectorAll(selector) {
      return selector === ".newsletter-form" && options.form ? [form] : [];
    },
    querySelector(selector) {
      return selector === "[data-article]" && options.article !== false
        ? article
        : null;
    },
  });
  const window = Object.assign(surface(), {
    document,
    innerHeight: 800,
    valonPlatform: { subscribe: "/subscribe", embed: "/embed/" },
    // A stale global must never act as an independent grant.
    valonAnalyticsConsent: true,
    consent_api: { cookie_prefix: "test_consent" },
    consent_api_get_cookie: (name) => name === "test_consent_statistics" ? choice : "",
    wp_has_consent: () => true, // Includes the API's permissive default case.
    gtag: (...args) => events.push(JSON.parse(JSON.stringify(args))),
  });
  if (options.api === false) delete window.wp_has_consent;
  if (options.gtag === false) delete window.gtag;
  vm.runInNewContext(code, {
    window,
    document,
    location: new URL(options.url || "https://www.valonasani.com/article/"),
    URL,
    performance: { now: () => now },
    sessionStorage: {
      getItem: (key) => storage.get(key) ?? null,
      setItem: (key, value) => storage.set(key, value),
      removeItem: (key) => storage.delete(key),
    },
    FormData: class {
      constructor(source) { this.data = source.data; }
      get(key) { return this.data[key]; }
    },
    fetch: async (...args) => {
      requests.push(args);
      return fetchResponse();
    },
    setInterval(callback, delay) {
      const id = ++timerId;
      timers.set(id, { callback, delay, next: now + delay });
      return id;
    },
    clearInterval: (id) => timers.delete(id),
  });
  return {
    window, document, form, events, requests, storage,
    named: (name) => events.filter((event) => event[1] === name),
    advance(ms) {
      const end = now + ms;
      while (true) {
        const due = [...timers.values()].sort((a, b) => a.next - b.next)[0];
        if (!due || due.next > end) break;
        now = due.next;
        due.next += due.delay;
        due.callback();
      }
      now = end;
    },
    setConsent(value, category = "statistics") {
      if (category === "statistics") choice = value;
      return document.dispatch("wp_listen_for_consent_change", {
        detail: { [category]: value },
      });
    },
    visibility(value) {
      document.visibilityState = value;
      return document.dispatch("visibilitychange");
    },
    scroll(value) {
      top = value;
      return window.dispatch("scroll");
    },
    click(url = "https://eepurl.com/h-inUL", placement = "hero") {
      const container = { dataset: { placement } };
      const link = {
        href: url,
        dataset: {},
        closest: (selector) => selector === ".newsletter-hosted" ? container : null,
      };
      // A nested span/image resolves to its containing link.
      return document.dispatch("click", {
        target: { closest: (selector) => selector === "a[href]" ? link : null },
      });
    },
    respond(fn) { fetchResponse = fn; },
    submit() { return form.dispatch("submit", { preventDefault() {} }); },
  };
}

test("only an explicit statistics allow enables custom analytics", async () => {
  for (const choice of ["", "deny", "unknown", true]) {
    const h = harness({ choice });
    h.advance(45000);
    await h.click();
    assert.equal(h.events.length, 0);
    assert.equal(h.window.valonAnalyticsConsent, false);
  }
  const missing = harness({ choice: "allow", api: false });
  missing.advance(45000);
  assert.equal(missing.events.length, 0);
  const anonymous = harness();
  await anonymous.setConsent("allow", "statistics-anonymous");
  anonymous.advance(45000);
  assert.equal(anonymous.events.length, 0);
  const allowed = harness({ choice: "allow" });
  allowed.advance(30000);
  assert.equal(allowed.named("article_engaged").length, 1);
});

test("late consent starts fresh and revocation discards accumulated reading", async () => {
  const h = harness();
  h.advance(40000);
  await h.setConsent("allow");
  h.advance(20000);
  await h.setConsent("deny");
  h.advance(60000);
  await h.click();
  assert.equal(h.events.length, 0);
  await h.setConsent("allow");
  h.advance(29000);
  assert.equal(h.events.length, 0);
  h.advance(1000);
  assert.equal(h.named("article_engaged").length, 1);
});

test("late API loading works while failures and misleading change events fail closed", async () => {
  const h = harness({ choice: "allow", api: false, article: false });
  await h.click();
  assert.equal(h.events.length, 0);
  h.window.wp_has_consent = () => true;
  await h.document.dispatch("wp_consent_type_defined");
  // The denied click is never replayed after initialization.
  assert.equal(h.events.length, 0);
  await h.click();
  assert.equal(h.events.length, 1);
  h.window.wp_has_consent = () => false;
  await h.click();
  assert.equal(h.events.length, 1);
  h.window.wp_has_consent = () => { throw new Error("API unavailable"); };
  await h.click();
  assert.equal(h.events.length, 1);
  assert.equal(h.window.valonAnalyticsConsent, false);
  const denied = harness({ article: false });
  await denied.document.dispatch("wp_listen_for_consent_change", {
    detail: { statistics: "allow" },
  });
  await denied.click();
  assert.equal(denied.events.length, 0);
});

test("hidden time and time spent away from the article do not count", async () => {
  const h = harness({ choice: "allow" });
  h.advance(29250);
  await h.visibility("hidden");
  h.advance(300000);
  await h.visibility("visible");
  h.advance(749);
  assert.equal(h.events.length, 0);
  h.advance(1);
  assert.equal(h.named("article_engaged").length, 1);

  const outside = harness({ choice: "allow" });
  await outside.scroll(2000);
  outside.advance(60000);
  await outside.scroll(0);
  outside.advance(29000);
  assert.equal(outside.events.length, 0);
  outside.advance(1000);
  assert.equal(outside.named("article_engaged").length, 1);
});

test("article needs both time and 75 percent progress and emits only once", async () => {
  const h = harness({ choice: "allow" });
  h.window.innerHeight = 500;
  h.advance(31000);
  assert.equal(h.events.length, 0);
  await h.scroll(-300);
  assert.equal(h.named("article_engaged").length, 1);
  h.advance(90000);
  await h.scroll(-350);
  assert.equal(h.named("article_engaged").length, 1);
});

test("missing or failing gtag does not permanently consume article engagement", () => {
  for (const throws of [false, true]) {
    const h = harness({ choice: "allow", gtag: false });
    if (throws) h.window.gtag = () => { throw new Error("not ready"); };
    h.advance(30000);
    assert.equal(h.events.length, 0);
    h.window.gtag = (...args) => h.events.push(args);
    h.advance(1000);
    assert.equal(h.named("article_engaged").length, 1);
  }
});

test("bfcache restore restarts reading without counting the frozen interval", async () => {
  const h = harness({ choice: "allow" });
  h.advance(20000);
  await h.window.dispatch("pagehide", { persisted: true });
  h.advance(300000);
  await h.window.dispatch("pageshow", { persisted: true });
  h.advance(29000);
  assert.equal(h.events.length, 0);
  h.advance(1000);
  assert.equal(h.named("article_engaged").length, 1);
  await h.window.dispatch("pagehide", { persisted: true });
  await h.window.dispatch("pageshow", { persisted: true });
  h.advance(60000);
  assert.equal(h.named("article_engaged").length, 1);
});

test("hosted Mailchimp link click has distinct bounded parameters and no subscriber claim", async () => {
  const h = harness({ choice: "allow", article: false,
    url: "https://www.valonasani.com/?utm_source=tiktok&email=private" });
  await h.click("https://eepurl.com/h-inUL?email=private@example.invalid", "landing");
  assert.deepEqual(h.events, [["event", "newsletter_signup_click", {
    language: "en", placement: "landing", source: "tiktok",
  }]]);
  for (const url of ["https://eepurl.com/another", "https://evil.example/h-inUL",
    "https://eepurl.com.evil.example/h-inUL", "mailto:private@example.invalid", "https://["]) {
    await h.click(url);
  }
  assert.equal(h.events.length, 1);
  await h.setConsent("deny");
  await h.click();
  assert.equal(h.events.length, 1);
  assert.equal(h.storage.has("valon_source"), false);
});

test("attribution browser storage is used only with statistics consent", async () => {
  const h = harness({ article: false,
    url: "https://www.valonasani.com/?utm_source=linkedin" });
  assert.equal(h.storage.size, 0);
  await h.setConsent("allow");
  assert.equal(h.storage.get("valon_source"), "linkedin");
  await h.setConsent("deny");
  assert.equal(h.storage.size, 0);
});

test("newsletter_submit records an accepted request without subscriber data", async () => {
  const h = harness({ choice: "allow", article: false, form: true });
  await h.submit();
  assert.deepEqual(h.events, [["event", "newsletter_submit", {
    language: "sq", placement: "landing", source: "website",
  }]]);
  assert.equal(h.requests.length, 1);
  assert.equal(h.form.resets, 1);
});

test("invalid, failed, honeypot and revoked in-flight signups produce no event", async () => {
  for (const kind of ["invalid", "http", "network", "honeypot", "revoke"]) {
    const h = harness({ choice: "allow", article: false, form: true });
    if (kind === "invalid") h.form.valid = false;
    if (kind === "honeypot") h.form.data.website = "spam";
    if (kind === "http") h.respond(async () => ({ ok: false, json: async () => ({}) }));
    if (kind === "network") h.respond(async () => { throw new Error("offline"); });
    if (kind === "revoke") h.respond(async () => {
      await h.setConsent("deny");
      return { ok: true, json: async () => ({}) };
    });
    await h.submit();
    assert.equal(h.events.length, 0, kind);
  }
});
