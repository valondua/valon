# WordPress Site Health maintenance — 17 September 2026

Final authenticated Site Health result: **Good — “Everything is running smoothly here.”** All tests finished with no critical issues or recommended improvements. The public website and existing administrator session work.

## Changes

- Updated Twenty Twenty-Five from 1.2 to 1.5. Removed inactive Read 4.5.5 from the served theme directory; recovery copies remain in the protected Updraft backup and private application snapshot. Active Valon 2.4.8 was preserved, including a concurrent theme update during maintenance.
- Enabled WP Super Cache 3.1.3 in Simple mode for anonymous pages. Admin, logged-in users, query-string requests, REST and sitemaps bypass the page cache. Content updates clear cached pages.
- Enabled SQLite Object Cache 1.6.5. Its database is outside the web root in `/var/cache/wordpress`, owned by `www-data` inside a mode-0700 directory. It persists between PHP requests and safely rebuilds after container replacement. The app remains a single replica.
- Added EXIF, Intl and Imagick 3.8.1 to PHP 8.3.33. Increased OPcache's interned-strings allocation from 8 MB to 16 MB after a further Site Health warning appeared.
- Added the Valon Cache Maintenance MU plugin to clear page caches after WP Pusher updates, normal software updates and theme changes.
- Made the complete WordPress installation persistent. The existing Railway app volume now mounts at `/data`; `/var/www/html` links to `/data/.valon-state-v1/wordpress`. Core, configuration, themes, plugins, media and Updraft backups survive runtime deployments. MySQL and its separate volume were preserved.
- Explicitly configured all three Railway app domains to target Apache port 80. The runtime normalizes Apache to its PHP-compatible prefork module at startup.

## Validation

Before migration, every staged regular file matched production: 10,024 application files plus 2,372 media files. No source symlinks required migration. Private application, database and media recovery archives were copied off the server and verified by SHA-256; media ZIP integrity passed.

An isolated full-site rehearsal tested language pages, articles, search, REST, sitemap, canonical redirects, real administrator cache exclusion, SQLite persistence/invalidation/expiry and WordPress image resizing through Imagick. Container replacement preserved all tested application/media files and rebuilt caches. Page-cache purge callbacks worked. A deliberately injected Apache MPM conflict reproduced the hosted failure and was repaired by the final startup code.

Live checks confirmed PHP extensions, OPcache 16 MB, active plugins and themes, persistent paths, cache permissions, and cache maintenance hooks. Separate PHP processes verified object-cache set/get/delete and TTL expiration. The real browser session remained authenticated; its homepage bypassed anonymous page caching. All 24 sampled live media/assets returned HTTP 200 with the expected content types. Site Health completed with zero outstanding items.

One article's SEO title changed during concurrent editing. Its rendered title was checked against the current Yoast database value rather than overwritten with the earlier baseline.

## Deployment incident and recovery

This maintenance caused a temporary public-site outage. Updating the volume mount immediately removed the old deployment and started an automatic old-source build; that build was cancelled. Two replacement deployments then exposed an Apache MPM conflict present at hosted runtime despite passing build/local checks. The final startup code explicitly removes competing MPM links and enables prefork. After startup succeeded, an implicit Railway port-8080 route still returned 502; setting domain target ports to 80 restored public access.

The final successful deployment is `5af9d0f5-af82-4fe7-8590-5df46dbd118f`. No production database restoration or rollback of current content was performed. A missed page-cache garbage-collection event caught up normally after recovery, clearing the final transient health warning.

## Recovery and ongoing operation

Runtime source and deployment instructions are in `hosting/railway/`. Do not blindly redeploy the August image, which bundled older application files. Runtime rollback must preserve the new persistent tree, `/data` mount and port-80 routing. A tested private rollback runtime also includes the final Apache startup correction.

The protected Updraft backup from 09:30 UTC and private local recovery archives remain available. Local copies are under `.local/site-health-backups/`, with directory mode 0700 and archive mode 0600. Original media at the volume root was retained as another recovery copy; active media lives inside the persistent WordPress tree. No recurring off-site backup destination or schedule was added.

Detailed local evidence is in `.local/site-health/`, `.local/site-health-host/runtime-validation.json`, and `.local/site-health-rehearsal/`. Task-created rehearsal containers/network were removed; pre-existing local development containers were preserved.
