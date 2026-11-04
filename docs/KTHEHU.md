# Current experience — 4 November 2026

The public form asks eight core fields, a support choice and privacy consent. Removed legacy questions remain optional in the schema so existing applications stay readable. Funding does not introduce extra required questions. Documents, a document link and podcast interest are optional within an expandable section.

Review is human-only. No application data is sent to OpenAI; AI generation and its dashboard controls have been removed. Confirmation email retries, private documents, review notes, access control and retention remain active.

# Kthehu initiative

The theme template `page-kthehu.php` provides the Albanian landing page. Valon Platform owns the submission API, private storage, mail queue, retention, and WordPress admin dashboard. No applicant account is needed. Existing unrelated workspace changes are not part of this feature.

## Launch setup

1. Deploy the theme and plugin together using the existing deployment process. Activate Valon Platform. Its versioned migration creates three InnoDB tables: `{prefix}vp_kt_applications`, `vp_kt_files`, and `vp_kt_limits`. Back up the database before deployment. Verify all three tables exist and use InnoDB before accepting applications.
2. Create a WordPress page with slug `kthehu`, title `Kthehu. Ndërto. Kontribuo.`, and template `page-kthehu.php`. Review it as a draft first; publish only when the services and privacy notice below are verified. Assign the page to Polylang language `sq`, set its ID in the `vp_kt_page_id` WordPress option, and keep the template assignment. The scoped routing module selects Albanian for this route and keeps its canonical URL at `/kthehu/`, including redirects from `/sq/kthehu/`. The template and theme navigation use Albanian on this route.
3. PHP requires `fileinfo` and `zip` (ZipArchive). Configure `upload_max_filesize = 10M`, `post_max_size >= 32M`, `max_file_uploads >= 4` (20 recommended), and `memory_limit >= 128M`. Set the reverse proxy request-body limit to at least 32 MB. A PHP limit of three files silently discards extras before application validation, so use the higher limit. Disable display of PHP errors on production and do not log request bodies or applicant data.
4. Configure authenticated transactional delivery for WordPress `wp_mail`, including sender domain SPF/DKIM. Verify receipt in a real controlled inbox. A successful `wp_mail` call only confirms transport acceptance, not inbox delivery. Failed sends retain the application and retry hourly up to five attempts; the dashboard supports manual retries. An interrupted sender can retry after its one-hour lease; the same reference identifies any duplicate email caused by a crash after transport acceptance.
5. Ensure WordPress cron runs at least hourly. The repository's scheduler is suitable; if `DISABLE_WP_CRON` is enabled, an external scheduler is required. The persistent application row is the mail queue. `vp_kt_process` handles new rows; `vp_kt_maintenance` recovers pending work, clears rate-limit buckets, and deletes records older than 24 months (including files and internal notes).
6. Administrators have access at **WordPress → Kthehu**. Grant `review_kthehu` only to explicitly authorized existing team accounts, e.g. `wp user add-cap USER_ID review_kthehu`. Revoke with `wp user remove-cap USER_ID review_kthehu`. Ordinary subscribers/editors do not receive access. Use the site's existing strong authentication controls.
7. Verify the linked privacy-policy page includes the real responsible entity, contact channel, hosting/backup retention, and applicable provider information. The initiative-specific notice explains the processing purpose, separate publication permission, and 24-month active-system retention. Team members can fulfill correction/export/deletion requests after checking the applicant's identity. Backup expiry remains a hosting responsibility.
8. Exclude `/wp-json/valon/v1/kthehu/*`, admin downloads, and exports from CDN caching. Responses explicitly send private/no-store headers. The landing page itself may be cached; a fresh signed form token is fetched separately.

## Privacy and review behavior

- Applications are custom database records, never posts or media-library attachments. Files are base64 chunks in a private table and have no public URLs. The application and all file chunks commit together. Database access and backups must follow the host's private-data controls; this feature does not add encryption at rest.
- Downloads require both the reviewer capability and a user nonce, and use forced attachment/octet-stream responses with `nosniff`. Uploaded files are untrusted and should be opened in a protected viewer; there is no antivirus service integrated.
- Files are checked for size, extension, actual image/PDF signatures or Office ZIP structure, macro payloads, traversal names, and expanded ZIP size. The server accepts at most three files of 10 MB each.
- A signed, expiring token, minimum fill time, honeypot, same-origin check, and ten submission attempts per IP/hour provide basic spam protection. The IP is stored only as a salted bucket hash for at most two hours. Do not trust arbitrary `X-Forwarded-For` headers. On the verified Railway HTTP-edge deployment, `vp_kt_railway_edge` is enabled and the code uses a valid `X-Real-IP` only when the Railway environment is present. Other deployments default to `REMOTE_ADDR`. Public-edge overwrite was checked using spoofed test headers; do not enable this option if an untrusted client can reach the service directly through another path. See [Railway request-header specifications](https://docs.railway.com/networking/public-networking/specs-and-limits).
- Unique token and application-content fingerprints stop replay and identical submissions across fresh forms. Retrying after a lost connection returns the original reference. A genuinely changed application may be submitted separately.
- The [official Responses API reference](https://developers.openai.com/api/reference/resources/responses/methods/create) informed the request format. `store: false` disables storing the response for later API retrieval; it is not a promise of zero provider retention.
- CSV export respects the active filters and neutralizes leading spreadsheet formulas. It includes personal data, notes; downloaded exports must be handled privately. Deleting a record does not recall files already exported or downloaded.

## Local verification

Docker was unavailable during implementation. A disposable WordPress Playground (PHP/WASM and SQLite integration) provides a second runtime. It is not proof of production MariaDB transaction behavior, real email deliverability, .

```sh
npm install --prefix .local/kthehu-runtime @wp-playground/cli @playwright/test
node tools/kthehu-preview.cjs
# In another terminal; requires installed Google Chrome:
node tools/kthehu-browser.test.cjs
node --experimental-wasm-jspi tools/kthehu-php-lint.cjs
```

The preview mounts this theme/plugin, creates `/kthehu/`, creates fake users, and captures mail without sending it. It runs at `http://127.0.0.1:9417/`; admin login is `admin` / `password`. Use only disposable local data. The test-control endpoint is copied into ignored `.local` and requires the local test constant and administrator access. Stop the preview when finished; it is not a deployment server.

The browser suite exercises mobile layout, required/conditional fields, submission, confirmation email contents, retry deduplication, server validation, private uploads/downloads, failed-email recovery, status/notes, filters, CSV escaping, anonymous access, an unauthorized logged-in account, 3 × 10 MB uploads, oversized uploads, rollback after a failed chunk write, cross-origin denial, rate limiting, and retention deletion. The syntax check parses all feature PHP under PHP 8.1 and 8.3. Screenshots are saved in `.local/kthehu-runtime/`.

Before marking production ready, repeat the flow on the actual MariaDB/PHP host with a real controlled inbox verify 3 × 10 MB uploads and database rollback on a failed chunk write, inspect mobile/desktop rendering, confirm the exact public URL and privacy contact, verify cron and retention, and test an authorized team account. None of these production checks is implied by local test success.

Design references: [private Lazyweb research](https://www.lazyweb.com/agentic-search/251841ac-6c93-44b6-a058-dca8161b6d63).

## Production verification — 4 November 2026

The page is published at `https://www.valonasani.com/kthehu/` (the requested apex URL redirects there). Page ID 5883 is assigned to Albanian. Production runs PHP 8.3.33 and MySQL 9.7.2; all three private application tables use InnoDB. The release preserves the current investigation and newsletter features. Database and active-theme/plugin backups were verified on the server and copied to private local storage before activation.

Live verification covered an anonymous browser submission, funding questions, podcast refusal, PDF/PNG uploads, a 3 × 10 MB submission, browser retry without duplication, and actual confirmation receipt in the owner's connected Gmail inbox. Owner and explicitly granted reviewer access were checked alongside denial for anonymous and unprivileged users, download nonces, exact downloaded file bytes, notes/status persistence, filters, and CSV exports. Canonical routing, Albanian markup, mobile/desktop widths, and existing homepage/language/investigation/REST/sitemap routes passed.
