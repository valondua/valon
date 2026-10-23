# Albania profile and short-route release — 23 October 2026

Adds six attributed photo profiles and connected detail panels for the existing 29 entities. Each panel preserves the role, direction of connection, evidence label and source; missing direct citations remain research leads. A static 29-profile fallback complements the existing 44-record evidence register. The graph defaults to the selected entity and its immediate connections, with a full-network control.

The public page keeps WordPress ID 5557 and its investigation template, moving to /albania/. Homepage/navigation links use the short route. A guarded permanent redirect keeps /albania-protests-investigation/ working once the page slug changes.

Validation: protected staging serves /albania/ with HTTP 200 and both old-path variants with HTTP 301. English, Albanian and German homepages each contain three updated links. All 14 changed runtime files matched release SHA-256 values on staging; PHP and JavaScript syntax checks passed. The 44 evidence records, 13 original sections and valid anchors remain. Desktop/mobile browser checks exercised photo selection, connected profile navigation and full-network control; the 390-pixel view had no horizontal overflow or console errors.

Deployment: use the existing WP Pusher theme update mechanism. Verify runtime hashes before changing the production slug, then verify public canonical, redirects and homepage links. The existing full database/files backup and restoration rehearsal remain available; this release also saves the prior changed files and page record outside the document root, with file restoration hashes checked. Backups are on the same hosting volume, not offsite.

Rollback: restore the prior application files and page 5557 slug from the release-specific protected backup, clean the post cache and flush rewrite rules. Do not restore the entire database over newer subscriber/content changes. Recheck both routes, navigation, indexability and newsletter behavior.
