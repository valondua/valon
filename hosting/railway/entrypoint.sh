#!/bin/sh
set -eu

# Fail closed rather than creating an empty installation or restoring stale source.
# The readiness marker is outside DocumentRoot; the migration operator creates it
# only after both source snapshots and staged state have passed verification.
state=/data/.valon-state-v1
root="$state/wordpress"
fail() { echo "Valon startup refused: $1" >&2; exit 1; }
[ -d /data ] || fail 'persistent volume missing'
[ -f "$state/READY" ] || fail 'verified migration marker missing'
[ "$(cat "$state/READY")" = 'valon-wordpress-state-v1' ] || fail 'unexpected state format'
for required in wp-config.php wp-settings.php wp-includes/version.php wp-content/themes/valon/style.css; do
    [ -f "$root/$required" ] || fail 'required WordPress state file missing'
done
[ -d "$root/wp-content/uploads" ] || fail 'uploads missing'
[ ! -L "$root/wp-content/uploads" ] || fail 'uploads must be independent to prevent recursive backups'
[ ! -d "$root/wp-content/uploads/.valon-state-v1" ] || fail 'private migration state was copied into uploads'
[ -L /var/www/html ] || fail 'document root must link to persistent state'
[ "$(readlink /var/www/html)" = "$root" ] || fail 'unexpected document root link'
php -r 'foreach (["mysqli", "gd", "zip", "exif", "intl", "imagick", "sqlite3"] as $m) { if (!extension_loaded($m)) { fwrite(STDERR, "Missing PHP module: ".$m."\n"); exit(1); } }'
php -l "$root/wp-config.php" >/dev/null
# Select the PHP-compatible MPM in the running filesystem as well as the image.
# Remove any competing MPM module links before Apache reads its configuration.
for module in /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf; do
    if [ -e "$module" ] || [ -L "$module" ]; then rm -- "$module"; fi
done
a2enmod mpm_prefork
apache2ctl -t
exec docker-php-entrypoint "$@"
