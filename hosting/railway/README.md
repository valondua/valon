# Production WordPress runtime

This directory is the Railway **app runtime**, separate from the Valon theme deployed by WP Pusher. It contains no website data, credentials, database backups, or WordPress salts.

The Dockerfile retains the production PHP 8.3 base and adds EXIF, Intl, and Imagick. PHP's interned-string buffer is 16 MB. The existing app volume must be mounted at `/data`; the complete WordPress installation lives in `/data/.valon-state-v1/wordpress`. `/var/www/html` points there. The startup script refuses to serve an absent or unverified installation.

`imagick-3.8.1.tgz` is the unmodified [official PECL source archive](https://pecl.php.net/get/imagick-3.8.1.tgz), including its upstream license. Its SHA-256 is `3a3587c0a524c17d0dad9673a160b90cd776e836838474e173b549ed864352ee`, verified during every build. Keeping this public archive in the build context avoids a dependency on PECL channel metadata being available.

The volume contains WordPress core, `wp-config.php`, themes, plugins, media, and Updraft backups. The MySQL service and its separate volume remain independent. Do not replace the persistent installation with an old application image or copy the private `wp-config.php` into this build context.

## Build and deploy

Build with `docker build --platform linux/amd64 -t valon-wordpress-runtime .`. Deploy **only this directory**, with an explicitly selected Railway project, app service, and production environment. With Railway CLI, use `railway up PATH_TO_THIS_DIRECTORY --path-as-root` and the explicit project/service/environment flags. Never deploy the theme repository root as the server image.

For an existing deployment using this layout, retain the `/data` mount, current environment variables, and single app replica. The readiness marker `/data/.valon-state-v1/READY` must contain `valon-wordpress-state-v1`. A fresh installation requires a verified restoration before that marker can be created.

Apache listens on **port 80**. All Railway domains for the app must explicitly use target port **80**, including the apex, `www`, and generated Railway domain. An unset target follows Railway's injected `PORT=8080` and produces HTTP 502 with this image. Apache's prefork module is selected during both image build and container startup; the startup step also removes competing MPM module links. Keep this runtime normalization: the first hosted builds passed local/build checks but failed at runtime with multiple MPMs loaded.

Changing a Railway volume mount path triggered an immediate automatic redeployment during this migration. Do not assume it only stages configuration. Routine future runtime deployments should retain the existing `/data` mount and explicit domain target ports.

## Caching

- WP Super Cache uses Simple mode for anonymous pages. Query-string pages, admin, REST, and sitemaps are excluded. Publishing or updating content clears page caches.
- SQLite Object Cache uses local `/var/cache/wordpress` storage owned by `www-data`, mode `0700`, outside the document root. Cache data can rebuild on restart; the plugin, drop-in, and configuration remain on the volume. Keep one replica and a local filesystem for this cache.
- `valon-cache-maintenance.php` is installed in `wp-content/mu-plugins`. It clears public page caches after WP Pusher theme/plugin updates, normal software updates, and theme switches.
- Keep existing WordPress cron behavior unless a separate scheduler has been configured and verified.

### Browser caching without a runtime deployment

`static-cache.htaccess` is a separately managed block in the persistent WordPress root `.htaccess`, outside the WordPress rewrite markers. It caches real public CSS, JavaScript, images and fonts for one day, or 30 days when the URL has a `ver` parameter. Bundled theme images, styles and scripts use file modification times for cache invalidation. Validators remain enabled; no `immutable` policy is applied. HTML, PHP, REST, redirects, errors and non-GET/HEAD responses retain their existing policies.

Run the installer as the existing `.htaccess` owner. It defaults to a dry run, preserves other rules, verifies ownership/source contents before atomic replacement, and requires a private recovery directory outside the web root when applying:

```sh
php tools/install-static-cache.php --root=/var/www/html
php tools/install-static-cache.php --root=/var/www/html --backup-dir=/data/PRIVATE_RELEASE_DIRECTORY --apply
python3 tools/static-cache-qa.py https://www.valonasani.com
```

This is a file update in the existing volume: it requires neither a container redeployment nor a mount change. Apache reads `.htaccess` on each request. Test the policy in an isolated Apache/WordPress environment first because `apache2ctl -t` alone does not validate per-directory `.htaccess` files. On a failed public smoke check, restore the saved `.htaccess` atomically with its previous owner/mode. Re-run the installer to verify idempotency after applying; keep the same managed block if WordPress refreshes its own rewrite section.

## Verification and recovery

After a runtime deployment, check Site Health, all three homepages (`/`, `/sq/`, `/de/`), an article, search, REST, sitemap, media, and the authenticated dashboard. Confirm a second anonymous request is served from page cache and object-cache data survives a separate PHP process. Confirm `wp-config.php` and private migration paths return HTTP 403.

Rollback the runtime while retaining the current persistent WordPress tree and MySQL database. The old August 2026 deployment bundled outdated theme/plugin files and must not be blindly redeployed. Private application/database/media snapshots and the pre-change Updraft backup were retained during the September 17 maintenance work. Original uploads at the volume root were retained as an additional recovery copy; current uploads are under the persistent WordPress tree.

Backups on the application volume share its failure domain. The September 17 task also made private local recovery copies; an ongoing off-site backup schedule remains a separate operational decision.
