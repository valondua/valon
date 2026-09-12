# Validation

The implementation was verified locally. The public website was not deployed or modified.

## Design and photography update — 12 September 2026

The theme is now version 2.1.0. The homepage uses a portrait-led navy introduction, a prominent newsletter invitation, selected media coverage, featured articles and interview cards. Eleven supplied photographs are placed across Home, Start here, About, Newsletter, Now and selected article cards. Page featured images can override the supplied page-introduction defaults in WordPress.

- Re-ran all **207 local HTTP assertions** after the final template changes: all passed. Existing URL, language, indexability and subscription error checks remain intact.
- PHP syntax passed for the theme templates and newsletter include; JavaScript parsing and `git diff --check` passed.
- Visually inspected desktop layouts at 1280/1440px and mobile at 390px. Checked English/Albanian homepage layout, language-specific signup preference, mobile navigation, search submission and Escape/focus behavior, newsletter layout, page portraits and article thumbnail crops.
- Confirmed selected English articles remain the curated fallback on Albanian pages while translations await approval. Original article dates and URLs are retained.
- Confirmed homepage interview thumbnails load and no video iframe loads with the initial page. Social feed authorization remains pending; profile links remain the fallback.
- Photo copies have EXIF/XMP/IPTC/comment metadata removed, retaining encoded image data and color profiles. Source photos were not modified. Below-fold photos use lazy loading; no field performance score is claimed.
- Rebuilt both release ZIPs and verified private drafts and development files remain excluded.

The integration and restore evidence below was recorded on 11 September. Provider behavior was not changed by this design update. No new article, translation or newsletter was published, and the live website remains unchanged.

## Environment

- WordPress 7.1, PHP 8.3 Apache, MariaDB 11.4.
- Polylang 3.8.9, Yoast SEO 28.4, Valon theme 2.1.0, Valon Platform 1.0.0.
- Review preview: localhost:8094, noindex, bound to 127.0.0.1.
- Separate restored database/site on localhost:8095 was used to verify indexable behavior, then stopped.

## Automated verification

- **49 integration assertions passed** using real WordPress storage and mocked provider responses. Coverage includes owner verification, repeat imports, encrypted token rotation, retry/backoff, cache preservation, hidden/deleted/private states, curated embeds, locked sync, approval gating, clear Gutenberg errors, Mailchimp pending status, language preference, existing-contact protection, consent and response privacy.
- **207 HTTP assertions passed** against the noindex review preview.
- **211 HTTP assertions passed** against the separately restored indexable instance.
- All **78 legacy post URLs and nine legacy page URLs** returned the expected local addresses.
- The exact broken Now destination resolved to /now/; a genuinely missing page retained HTTP 404.
- Indexable mode emitted self-canonicals, real bilingual alternates, Person/ProfilePage identity and a sitemap containing the 78 original articles. Unapproved translations were absent.
- Private editorial/dev paths were inaccessible over HTTP. Release ZIP checks exclude private drafts, .env, .git, backups and development files.
- PHP syntax checks and JavaScript parsing passed.

The JSON reports preserve the HTTP assertions. The integration suite is reproducible with tools/qa.php; it cleans up test records and does not send emails or call platforms.

## Browser checks

Verified desktop and 390px mobile views, mobile menu, newsletter navigation, Albanian form preference/error message, article reading with no horizontal overflow, and the local admin connection/review queue.

Homepage players are deferred. Existing supported video iframes in archive content are also deferred at render time. The subscription form honestly reports unavailable while credentials are absent.

No field Core Web Vitals score, real platform authorization, real embed availability, sender deliverability or real subscriber conversion has been claimed.

## Restore rehearsal

Exported the local database and uploads, restored the database to a separate local database, and served that restore through an independent localhost-only instance. Its 78 published articles and eight draft translations were retained; the indexable HTTP audit passed.

The rehearsal revealed stale language/SEO URLs when only the home/siteurl options changed. A serialized-data-aware origin replacement and transient/cache refresh corrected them. This is now an explicit migration step in DEPLOYMENT.md.

This verifies the local restoration path, not backups or rollback on the existing production host.

## Still required for launch

- Existing-host administrative audit, protected staging and verified production backups/rollback.
- Actual TikTok/Meta app approval, account tokens, permissions and hourly connection tests.
- Mailchimp audience/sender/merge-field setup, approved bilingual response copy and authorized full subscription/unsubscribe tests.
- Search Console baseline and consent-aware GA4 wiring.
- Valon's approval of core copy, privacy details, articles, translations and newsletters.
- Final staging review, representative mobile performance testing and production rollout acceptance.

### Swiss Standard German and dashboard deployment

- 262 local HTTP assertions passed for all three language editions and legacy URLs.
- 54 integration assertions passed, including German Mailchimp preference, double opt-in request, Swiss locale and unsupported-language fallback.
- Dashboard migration rehearsed against a restored local database: all 42 core pages staged without changing published content, modified drafts rejected, and original page IDs/slugs retained after publication. Anonymous requests and malformed language bundles rejected; uploaded HTML sanitized.
- PHP syntax checks passed on PHP 8.3; formatting targets PHP 8.1. Plugin JavaScript syntax passed.
- German homepage visually checked at 390px. Original article copy stays in English until its editorial translation is approved.
