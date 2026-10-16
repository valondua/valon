# Owner workspace

`/dashboard/` reuses WordPress cookie authentication and the existing positive `vp_editorial_owner` ID plus `manage_options`. An unset owner fails closed. Do not change accounts, roles, credentials or the owner option to enable this feature. The route is handled before canonical redirects and does not create a page, REST route, public index, or feed entry. Direct PHP includes exit without WordPress.

The renderer deliberately skips theme hooks, analytics and remote assets. CSS contains no data. Inventory is JSON in `valon_dashboard_inventory`, with autoload off; direct SQL reads/writes avoid persistent object caching. Do not register the option with REST or put inventory, screenshots, reports or exports in Git, assets, uploads, release ZIPs or a public Page. Database backups remain private.

## Inventory

Outside-document-root JSON has `items` and optional `overview_ids`. Each item has `id`, `area`, `title`, `status` (`needs-valon`, `in-progress`, `blocked`, `done`), `summary`, `detail`, `next`, `verified`, optional `as_of`, and `sources` (`label`, HTTPS `url`). Keep reported periods separate from verification dates; missing source links stay absent. Links preserve their destination's permissions. Email summaries should be minimal. Keep proposed work distinct from completed work.

After approval, load through existing WP CLI as the confirmed owner:

```
wp --user=EXISTING_OWNER_ID eval-file wp-content/themes/valon/tools/dashboard/import.php /data/PRIVATE_RELEASE_DIRECTORY/inventory.json
```

Never store the JSON in the web root, even temporarily. No integration schedules, account connections or publication actions run from this dashboard.

## Deployment gate

This branch is a draft. WP Pusher automatically deploys main; do not merge until activation is approved. Before enabling:

1. Back up affected theme files and the prior inventory in a private release directory. Confirm deployed base hashes and existing owner identity.
2. Add `/dashboard` to WP Super Cache rejected URI configuration and confirm authenticated users are excluded. Purge any existing dashboard cache entries. Do not disable site-wide caching or alter other cache settings. Code also sets `DONOTCACHEPAGE` and private/no-store response headers.
3. Install only the reviewed theme change through the established workflow. No runtime rebuild or volume change is needed.
4. Import the private JSON separately. Do not put it in the PR.
5. Test actual WordPress login/logout and anonymous requests to `/dashboard`, `/dashboard/`, query-string and encoded variants before and after an owner visit. Anonymous responses must only redirect; another logged-in admin must receive 403. Verify no private content in response bodies, public REST, search, sitemaps or cache files. Confirm no-store/noindex headers and no third-party requests. Repeat after a cache warm-up.
6. Check existing homepage/article navigation and desktop/mobile dashboard sources. Authenticated destination links require their own access; URL shape alone is not source-access verification.

The production read-only preflight on October 16 confirmed existing Valon owner/admin, current Valon theme, enabled page cache, and no dashboard page or inventory. The cache exclusion list did not yet include `/dashboard`. No production settings were changed.

## Local validation

PHP 8.3 syntax checks and `python3 tools/dashboard/test.py` (with PHP available) test owner access, fail-closed states, route variants, HTML escaping and unaffected routes using stubs. They do not replace real WordPress session/cache acceptance testing. Desktop and 390px browser preview checks validate rendering and navigation using a private fixture outside the repo. The fixture contains no real login flow.

## Rollback

Restore prior theme files and remove the private inventory option through the existing protected admin workflow. Retain the dashboard cache exclusion until cache absence is verified. Do not overwrite unrelated database content or restore an old runtime image.

## Weekly improvement register

Use `python3 tools/dashboard/tasks.py --register /PRIVATE_DIRECTORY/tasks.json --input /PRIVATE_DIRECTORY/proposals.json`. Input is `{ "tasks": [...] }`; fields: canonical `url`, `problem_type`, `title`, `evidence`, `proposed_change`, `success_criterion`, `evaluation_period`, `verified`, and optional priority/language/cohort context. IDs deterministically combine canonical URL and normalized problem type. Repeated observations reuse IDs, including completed tasks, and preserve status. The file is atomically written with mode 0600 under a lock. Keep the register outside any web root. This is the local fallback, not an active schedule.

Include the register's `tasks` in the private inventory during an approved import. The dashboard renders these proposals under Weekly SEO tasks. No task performs a rewrite, creates an unrelated issue or sends outreach. Keep source-timezone differences, the confirmed-signup tracking start and missing article/organic attribution explicit in each comparison.
