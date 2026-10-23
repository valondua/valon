# Server-confirmed newsletter measurement

The Mailchimp signed `subscribe` callback records the contact confirmation and queues `newsletter_confirmed` for GA4 without a browser return. New website attempts capture the existing GA client ID and (when available) session ID through `gtag get` only with explicit statistics consent. No GA ID is fabricated. Confirmations without consent or a valid client ID remain in the newsletter ledger and are not sent to Google. Old attempts without these fields are not backfilled.

WordPress privately stores the Measurement Protocol secret in the non-autoloaded `vp_newsletter_ga4_config` option, with `enabled` and `api_secret` keys. The measurement ID is fixed to `G-9264D19BZD`. The secret must never be exposed through script configuration, public REST responses, logs or Git. Google receives only the original client/session identifiers, confirmation time, bounded language/source/placement and method. No email, hashed email, member hash, receipt, IP address or URL is sent. Ads user data and personalization are explicitly denied.

The receipt retained in the browser is used only to revoke pending server reporting when analytics consent is explicitly withdrawn. Unknown/startup consent does not erase it. Revocation erases GA identifiers for pending attempts for that contact. A withdrawal observed after an event has already been sent cannot undo that event; this is not a retroactive GA data-deletion mechanism. Mailchimp unsubscribe/cleaned notifications also disable pending reporting. A consent change in a different browser cannot be observed by this browser receipt.

An atomic queue claim prevents concurrent cron/webhook delivery. Duplicate signed callbacks do not duplicate the queue entry. Cached older JavaScript receives `finished: true, confirmed: false` from the old browser endpoint, so it cannot double-report. WordPress dispatches immediately and checks pending work every five minutes. The host must run due WordPress cron events independently of page visits. HTTP 2xx is labelled **accepted**, not verified GA ingestion. HTTP failures are labelled **failed**; timeouts or abandoned submissions are **uncertain**. These are not automatically retried because GA4 custom events offer no reliable idempotency key, and retrying an ambiguous delivery could create duplicate conversions. Review these states before any manual recovery. Events older than 71 hours are marked expired rather than silently moved to today's date.

The administrator newsletter report shows delivery status separately from confirmed subscription totals. Test addresses remain excluded from subscriber totals. A private `test_member_hashes` allowlist can temporarily enable an end-to-end test: the actual `newsletter_confirmed` payload then carries `validation_test: 1` and `debug_mode: true`. Such tests can appear in GA4 key-event totals; record and disclose them, and remove the allowlist after verification. The email itself is never sent to Google.

GA client/session IDs and receipts are retained with intents for up to 30 days; the confirmation/delivery ledger has a 400-day retention period. The original session ID is included only within 24 hours of that session. The server does not invent website engagement time for an email confirmation.

Validation: `node --test tools/newsletter-measurement.test.cjs tools/analytics-consent.test.cjs tools/newsletter-popup.test.cjs`; run `tools/newsletter-measurement-qa.php` with WordPress loaded. PHP tests use temporary tables and mock all outbound HTTP. For release, validate an allowlisted test payload against Google's `/debug/mp/collect`, then complete an actual Mailchimp double-opt-in and verify the actual event in GA4 with the website tab closed.

## Popup trigger attribution (October 23, 2026)

New openings record `placement=popup_exit` for desktop exit intent, `popup_timed`
for the existing timed/reading-depth trigger, and `popup_manual` for explicit opens.
The actual opening trigger persists through the consented submission intent,
confirmation ledger, and GA4 newsletter_confirmed payload. Later pointer movement
cannot relabel an open popup. Legacy `popup` remains mixed/unknown; no backfill.
Consent and test exclusions are unchanged. Compare confirmed counts, not causal
lift or conversion rates: triggers are not randomized and no impression denominator
is recorded. Non-consented/unknown attribution stays unknown.

## Consented popup exposure cohorts

`newsletter:popup-open` fires after `showModal()` succeeds. A view is recorded only
when statistics consent is explicitly allowed, the page is visible, and an existing
valid GA client ID is available. No late-consent backfill. Logged-in users are excluded.
The server stores a keyed hash of that ID, opening placement, timestamp and random
receipt in `vp_newsletter_impressions`; it does not store raw client IDs or emails there.
The form's consented intent references the validated receipt. No extra GA conversion
is sent for a view; the server ledger supplies the report denominator independently
of GA report availability. Exposure records expire after 30 days.

`vp_newsletter_popup_report(start_utc,end_utc)` uses a half-open exposure cohort and
confirmations received by the cutoff. Per trigger it returns impressions, distinct
browser visitors, matched confirmed browser visitors, confirmed contacts and
`confirmed_viewer_rate = confirmed_viewers / viewers`. Repeated views and webhook
retries cannot multiply the numerator. Test browser IDs known from configured test
contacts are excluded from both numerator and denominator. Unidentified QA browsing
cannot be recognized automatically; use signed-in admin browsing for manual checks.
People across devices are not deduplicated. Consent-denied views and missing GA IDs
are unmeasured, not zero. Legacy unmatched confirmations are totals only. Pending
confirmations can mature later; this is observational, not a randomized comparison.

## October 24: popup measurement independent of GA

Popup views now use a random 128-bit first-party visitor ID (`valon_popup_visitor`),
created/read only with explicit statistics consent, with a 30-day expiry. If browser
storage is unavailable it remains in memory for that page only. Withdrawal clears
this ID and in-memory view associations and retains the existing receipt-revocation
flow. The server stores its keyed hash, not the raw ID. No fingerprint or IP is used.
This ID is never substituted for a GA client ID or sent to Google.

Confirmation events now store their matched intent token hash, so rate calculation
is independent of the GA queue. Known test visitor hashes are excluded on both sides
of the rate. Legacy GA-based views remain compatible; missing historical exposures
are not invented. GA4 itself still requires an actual GA client ID and consent.
This supersedes the GA-ID requirement in the earlier exposure-cohort section.
