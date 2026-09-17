# Measurement contract

## Connected services

Verified setup as of **17 September 2026**:

- **Site Kit 1.187** is the sole GA tag owner: GA4 property `339908492`, measurement ID `G-9264D19BZD`, Google tag `GT-NCNVSDL`. The theme and platform plugin do not install another Google tag.
- **Search Console:** the URL-prefix property `https://www.valonasani.com/` is verified, linked to Analytics, and has its sitemap submitted. Reports can take time to populate; connection does not guarantee that every page is indexed.
- **WordPress reporting:** Site Kit displays Analytics and Search Console data in WordPress. Its dashboard is subject to Google processing delays and is not a real-time report.
- A **Search Console heartbeat** is scheduled at a 48-hour interval for a substantive follow-up check. It pauses after completing that check.

## Consent and custom events

**Complianz 7.5.5**, **WP Consent API 2.1.0**, and Site Kit Consent Mode are active. The banner offers Accept, Reject, and Preferences worldwide. Complianz communicates choices through WP Consent API; Site Kit manages Google consent updates.

The platform plugin removes Site Kit's regional restriction from the denied defaults so the worldwide opt-in banner and Google's initial consent state agree.

The banner has English, Albanian and Swiss Standard German copy, including the persistent manage-consent control on desktop and mobile. `tools/configure-consent-banner.php` previews the required Complianz copy/settings and Polylang translations; run it with WP-CLI `--user=<administrator> eval-file`, adding `apply` only to save. It preserves existing target translations. The platform plugin supplies missing template-label translations and resolves existing published policy links in the current language.

The platform bridge fails closed: custom events require both an explicit `allow` value for the WP Consent API statistics cookie and `wp_has_consent("statistics") === true`. An unset or anonymous-statistics choice does not authorize these events. `window.valonAnalyticsConsent` reflects that checked state; setting the global alone does not grant permission. Pre-consent interactions are not queued or replayed. Revocation blocks subsequent custom events and clears stored source attribution.

This custom-event rule is separate from Site Kit's Google tag behavior. Consent Mode can load Google code and send cookieless signals before acceptance; do not describe this setup as blocking all Google requests before consent. A configured banner and passing tests are not a claim of legal compliance. Credentials and API keys never belong in front-end code.

| Event | Trigger | Bounded parameters | Meaning |
|---|---|---|---|
| newsletter_signup_click | Click on the owned Mailchimp link `https://eepurl.com/h-inUL` | language, placement, source | An intention to sign up, not a form submission or confirmed subscriber. |
| newsletter_submit | Successful accepted response from the on-site newsletter form | language, placement, source | A request, potentially for an already-known address. Not a confirmed subscriber. |
| article_engaged | At least 30 consented seconds while the document and article are visible, plus 75% article progress | article_id, language | A reading signal, sent at most once per page instance; not proof of comprehension. Consent withdrawal resets accumulated reading time. |
| social_post_open | Visitor requests a social player | platform | Player requested, not confirmed play completion. |
| social_source_click | Visitor follows the original post | platform | Outbound source click. |
| page_not_found | Rendered 404 page | page_path without query | Broken journey to investigate. |

Never add email, Mailchimp member hash, IP address, token, raw form data or free text as analytics parameters. The public response contains a localized generic message only.

There is **no confirmed-subscription GA event** in this implementation. Hosted Mailchimp form completion happens off-site and is not established by the outbound click. Use Mailchimp subscriber status and opt-in records for subscriber reporting; a subscribed status alone does not prove a completed double opt-in flow.

## Mailchimp status

The production signup destination remains the existing hosted Mailchimp form. Account sending capacity is a separate operational prerequisite for a full confirmation-email test. Keep subscriber totals and account-state evidence in private records; do not publish them with the website source.

## Attribution and reporting

UTM convention:

```text
?utm_source=tiktok&utm_medium=organic_social&utm_campaign=personal_brand&utm_content=bio_sq
?utm_source=linkedin&utm_medium=organic_social&utm_campaign=letter_01&utm_content=post_en
?utm_source=newsletter&utm_medium=email&utm_campaign=letter_01&utm_content=read_en
```

The on-site form carries only an allowlisted source category into Mailchimp VSOURCE, with explicit VLANG. This does not establish the same fields for the externally hosted Mailchimp form. Source attribution is stored in the browser only with explicit statistics consent. Detailed campaign attribution stays in consented GA4. Do not claim exact campaign-level confirmed-subscription matching without a separately verified implementation.

Monthly report by source and language:

- New confirmed subscribers and unsubscribes from Mailchimp, using confirmed status/date and VLANG/VSOURCE where those fields are populated and verified.
- Hosted signup-link clicks from `newsletter_signup_click`, reported separately from form requests and confirmed subscriptions.
- Attributed signup request rate = accepted form requests / eligible landing sessions; distinguish this from confirmed signup rate.
- Confirmed signup rate by source = newly confirmed members with that source / attributable eligible sessions over a matching window. Record cross-device, delay and consent limitations.
- GA4 engaged sessions and article_engaged counts, with sessions/users/views distinguished.
- Search Console organic landing-page clicks/impressions, query mix and indexability.
- Newsletter clicks and replies. Opens are a secondary, privacy-affected signal.

Use consistent periods and record report timezone, filters and definitions. Do not combine social video views, impressions, profile visits and GA sessions into a measured funnel. Establish the real baseline before forecasting growth.

## Verification

The current implementation passed **11 local behavior tests** and **7 public-script integration simulations**. Run them from the repository root:

```sh
node --test tools/analytics-consent.test.cjs
node tools/analytics-public-replay.cjs --live-bridge --require-cmp
```

The local tests cover explicit consent, delayed consent, withdrawal, visible reading time, back/forward cache behavior, newsletter requests, and bounded event parameters. The public replay downloads the deployed bridge and public Site Kit/WP Consent API scripts, then runs them with a mocked DOM and cookie store in Node. It also replays Complianz's published consent-to-API adapter. It verifies queued consent updates and custom-event gating without loading Google transport or sending production events.

These simulations alone do **not** prove actual browser behavior or Google network delivery. Separate live checks on 17 September verified Google's denied initial consent state in Tag Assistant, a saved statistics-only browser choice, and the ability to reopen the banner and reject optional cookies. A consented anonymous article visit and newsletter-link click each appeared once in GA4 Realtime as `article_engaged` and `newsletter_signup_click`. A second newsletter click after rejecting optional cookies left the signup-click count unchanged during the subsequent realtime check. Those two events are deliberate setup-test traffic, not organic reader activity. The on-site form's accepted-request event is covered by tests; production currently uses the hosted Mailchimp form.

Confirmed Mailchimp subscriptions must be assessed in Mailchimp, independently of GA events. A new end-to-end email confirmation test remains dependent on the owner resolving the account sending-capacity prerequisite.
