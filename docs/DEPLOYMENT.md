# Staging, launch and rollback

## Required before production

Identify the existing hosting project and create a protected staging environment on that host. The public website must stay unchanged while review is pending.

1. Audit actual WordPress core, PHP, active/inactive plugins, theme, administrator accounts and update compatibility.
2. Take a full database backup, wp-content backup, host configuration snapshot and media inventory. Record backup time/checksums and prove restoration in a separate database/site.
3. Capture Search Console indexing, performance, sitemap and links baselines; export the Mailchimp personal audience configuration and aggregate baseline. Keep personal data in protected storage.
4. Clone the existing database/media into staging. Scrub production sending credentials, disable outgoing email and protect access. Set WP_ENVIRONMENT_TYPE=staging and blog_public=0.
5. When the hostname changes, use a serialized-data-aware WordPress search-replace from the old origin to staging (skip GUIDs), clear transients and caches, and rebuild Yoast indexables in the appropriate environment. Recheck Polylang homepage links; changing only the home/siteurl rows can leave stale language URLs.
6. Install the two release ZIPs, keep Yoast, add Polylang and test compatible versions. Do not import the public snapshot over the existing production database.

## Content and routes

Keep the private editorial bundle **outside the document root**, for example /srv/private/valon-editorial. The release ZIPs and public GitHub source exclude the bundle.

Run these in staging after backups:

```sh
wp valon languages
wp valon scaffold --content-dir=/srv/private/valon-editorial
wp valon translations --content-dir=/srv/private/valon-editorial
wp valon topics --content-dir=/srv/private/valon-editorial
```

Scaffold creates drafts, with replacement drafts pointing to existing page IDs. It does not overwrite live page content. New core pages and eight archive translations require Valon's review. Review exact content and SEO descriptions. Core homepage introduction is editable in Gutenberg. Portrait is configurable in the Customizer.

Set `wp option update vp_editorial_owner ACTUAL_VALON_ADMIN_ID`. Only that existing administrator can approve drafts through the admin screen. Do not assume the production owner ID is 1.

After approval, inspect `wp valon promote_pages` (dry run), then `wp valon promote_pages --apply` to apply only owner-approved core pages. This preserves existing page IDs/slugs. Articles remain drafts until their normal reviewed publishing action.

Inspect private topics.json before `wp valon topics --content-dir=... --apply`. This changes categories and featured selection, not article bodies or dates.

Keep the English permalink structure and every original article slug. English has no language prefix; Albanian uses /sq/. Polylang supplies alternates only for published translation pairs. Never redirect readers by location or browser language. The exact broken /?page_id=1233 address redirects only when the new Now page is published.

Do not replace legal content with the draft privacy page until operator details, processing and retention are reviewed. Review old archive health/financial claims without silently changing their original publication dates.

## Acceptance checks

- All existing 78 post and 9 page URLs work, and important incoming links remain relevant.
- Navigation, search, pagination, mobile menu, 404 recovery and the Now redirect work.
- Bilingual pages, subscription states and actual confirmation/welcome/unsubscribe journeys work.
- An unapproved article, translation or changed approved draft cannot publish.
- Hourly imports run without traffic, with no duplicate records; test actual approved accounts.
- Real token renewal, 429 handling, confirmed deletion/private states and unavailable embeds have been tested.
- Public responses and analytics contain no credentials or subscriber addresses.
- Production canonicals, reciprocal alternates, robots, sitemaps and Yoast Person/ProfilePage/Article graph are validated.
- Mobile keyboard use, reduced motion, readable type and deferred players are checked.
- Measure Core Web Vitals on staging under a representative mobile connection and again after launch; local visual testing is not a field-performance score.
- Database, uploads and application rollback have been rehearsed.

Yoast 28.4 omits canonical tags on noindex pages. The protected local/staging site is intentionally noindex. Validate canonical output again in the intended indexable production configuration before opening indexing.

## Rollout

Use the current host's deployment mechanism. Upload only dist/valon-theme.zip and dist/valon-platform.zip through the approved deployment/admin route. Review the final staging design and copy before production activation. Keep the prior theme/plugin release available.

Apply approved pages and topic mapping, configure server secrets and the independent scheduler, connect the consent-aware existing GA4 tag, verify Mailchimp, then activate the theme. Enable production indexing only after the final checks; submit the sitemap and request inspection of important pages in Search Console.

No social profile bio or newsletter campaign should be changed/sent merely by deploying this code.

## Rollback

Record the deployed commit, release checksums, previous active theme/plugin versions, database/media snapshot and host settings. If navigation, newsletter, rendering or indexability regresses:

1. Put the site behind the host's temporary maintenance/protection mechanism if needed.
2. Restore the prior application release and relevant configuration.
3. If approved content migrations were applied, restore the verified database snapshot, accounting for any new subscribers/content since the snapshot before overwriting.
4. Restore uploads only when changed. Recheck login, key URLs, newsletter settings, robots and the sitemap.
5. Keep the failed release and sanitized logs for diagnosis.

The local rehearsal uses a separate restore database. It does not prove that the existing production host is backed up or restorable.

## September 2026 three-language launch

Swiss Standard German uses `de_CH` / `de-CH` with `ss`, alongside English and Albanian. The `/de/` edition has its own core pages and newsletter language preference. The original English article archive remains available; unreviewed article translations remain drafts.

When SSH is unavailable, install Valon Platform through WP Pusher from `valondua/valon`, repository subdirectory `plugins/valon-platform`. Tools → Valon website launch provides nonce-protected, administrator/owner-restricted language setup, draft staging and explicit publication. Staging keeps published page content intact; reviewed replacements use distinct temporary slugs so WordPress cannot rename legacy URLs. The launch accepts only known page routes and languages, sanitizes HTML and rejects changed drafts at publication.

`includes/launch-copy.php` contains the core website copy authorized for public launch, including translations of the existing privacy text. It contains no article drafts, analytics exports, credentials or subscriber data. The separate private editorial bundle remains excluded. The dashboard can use this bundled copy without uploading files; a replacement JSON upload remains optional. No articles or newsletter campaigns are published by the launch action.

Production backup on 2026-09-12 at 17:40 UTC: UpdraftPlus database, plugins, themes, uploads and other wp-content files, approximately 301.5 MB. The backup completed and all components passed UpdraftPlus restore preparation. A local database restore and full stage/publish rehearsal also passed; this is not a completed restore on the production host. PHP 8.3.33 is compatible with the release. WP Pusher tracks main with automatic theme deployment, so merge only at the planned activation point.
