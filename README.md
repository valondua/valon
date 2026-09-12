# Valon Asani WordPress rebuild

Custom editorial theme and a separate **Valon Platform** plugin for owned social feeds, editorial review, a bilingual Mailchimp signup journey and personal-brand SEO.

This branch is based on the existing `valondua/valon` theme. It does not deploy a website or publish any editorial content by itself.

## Components

- Theme at the repository root: Gutenberg pages, article/topic/search templates, accessible mobile navigation, newsletter invitations, deferred social/video players and English/Albanian navigation.
- `plugins/valon-platform`: private social records, TikTok/Instagram/Facebook adapters, curated LinkedIn/X URLs, encrypted rotating tokens, hourly sync, review controls, Mailchimp double opt-in and Yoast identity integration.
- `tools`: local setup, mocked integration tests, route audit and release packaging.
- `docs`: deployment, measurement and validation instructions.
- `review` (ignored, delivered privately): bilingual core copy, eight article translation drafts, topic mapping, newsletter copy and editorial operations. This content is intentionally absent from the public repository and release ZIPs.

## Local WordPress

Docker Compose binds the preview to **127.0.0.1:8094**, with PHP 8.3, MariaDB 11.4 and a separate scheduler. The database passwords in Compose are local-only.

```sh
docker compose up -d wordpress scheduler
docker compose run --rm cli wp core install --url=http://localhost:8094 --title="Valon Asani" --admin_user=valon_preview --prompt=admin_password --admin_email=preview@example.invalid --skip-email
docker compose run --rm cli wp plugin install polylang wordpress-seo --activate
docker compose run --rm cli wp plugin activate valon-platform
docker compose run --rm cli wp theme activate valon
docker compose run --rm cli wp valon languages
```

Place the private review bundle at `review/content/`. Public archive snapshots belong in the ignored `.local/source/posts.json` and `.local/source/pages.json`.

```sh
docker compose run --rm cli wp valon import_public /var/www/html/wp-content/themes/valon/.local/source
docker compose run --rm cli wp valon scaffold --preview
docker compose run --rm cli wp valon translations
docker compose run --rm cli wp valon topics --apply
docker compose run --rm cli wp eval-file /var/www/html/wp-content/themes/valon/tools/prepare-preview.php
```

`--preview` is restricted to the local environment and publishes core preview pages only. Article translations remain drafts. All nonlocal scaffold runs create review drafts and preserve published pages. On another theme, activate Valon before scaffolding its topic structure.

## Verify

```sh
docker compose run --rm cli wp eval-file /var/www/html/wp-content/themes/valon/tools/qa.php
python3 tools/http-audit.py
node --check js/site.js
node --check plugins/valon-platform/assets/platform.js
python3 tools/package.py
```

The HTTP audit needs the private page manifest and public archive snapshots. The integration suite uses fake provider responses, makes no external requests, sends no email and removes its test records.

See [deployment](docs/DEPLOYMENT.md), [integration setup](docs/INTEGRATIONS.md), [measurement](docs/MEASUREMENT.md) and [validation](docs/VALIDATION.md).

## Editorial approval

Set `vp_editorial_owner` to Valon's existing administrator user ID. The Social queue → Connections & review screen lets that owner approve an exact draft revision. Approval does not publish. Changing approved content invalidates its approval. All new article publications are gated, even when a draft was created outside the social queue.

For existing published articles, create a separate draft for substantive editing: editing an approval-gated published article without fresh approval returns it to draft. This behavior deliberately blocks unreviewed changes but should not be used as a revision workflow.

## Assets and references

The homepage uses Valon's existing [public portrait](https://bethe.one/images/be-the-one-ai-personal-growth-app-founder.jpg). Public archive content and its original image URLs are preserved in the local preview; the existing production media library remains the migration source of truth. A configurable portrait is available in the WordPress Customizer.

Keep the current hosting, WordPress, Yoast and Mailchimp. The local preview is not evidence that production access, backups, social app permissions, deliverability or Search Console have been verified.
