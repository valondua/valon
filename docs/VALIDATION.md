# Validation — 11 September 2026

The implementation was verified locally. The public website was not deployed or modified.

## Environment

- WordPress 7.1, PHP 8.3 Apache, MariaDB 11.4.
- Polylang 3.8.9, Yoast SEO 28.4, Valon theme 2.0.0, Valon Platform 1.0.0.
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
