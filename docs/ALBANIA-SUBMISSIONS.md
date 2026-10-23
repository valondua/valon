# Investigation submissions and Albanian edition

The investigation links to a separate submission page. English is the default; Polylang pairs the investigation and submission pages with Albanian editions. The Albanian text is machine-assisted and includes a translation disclosure. Original evidence URLs, entity IDs, dates, filter values and attribution categories remain unchanged.

## Activation requirements

Do not merge to main until these steps can be completed: WP Pusher automatically deploys main.

1. Follow DEPLOYMENT.md. Retain the tested full backup and current theme/page/option before-images. Preserve submissions received after a release when rolling back.
2. Configure a sending provider in WP Mail SMTP. The form recipient is **valon@dua.com**. The From address must be authorized by the provider. Neither a recipient address nor the newsletter's Mailchimp Marketing connection supplies outbound mail. Keep API keys in the provider/plugin configuration, never Git.
3. Create private persistent storage outside the WordPress root, owned by the PHP worker, directory mode 0700; set `valon_tip_storage_dir` to its absolute path. The code rejects paths inside ABSPATH. Retain this directory and the custom table in protected backups.
4. Call `valon_tips_install()` to create/update the prefixed `valon_tips` table. Configure `vp_editorial_owner` to the existing authorized administrator.
5. Create an English child page of investigation page 5557 with slug `submit` and template `page-albania-submit.php`; store its ID as `valon_albania_submit_en`.
6. Create the Albanian investigation with slug `shqiperia`, template `page-albania-protests-investigation.php`, language `sq`; store ID as `valon_albania_sq_page`. Create its `dergo` child using the submission template and option `valon_albania_submit_sq`.
7. Save Polylang translation pairs for both page sets; flush rewrites and page caches. Verify the HTTP routes `/albania/`, `/albania/submit/`, `/sq/shqiperia/`, `/sq/shqiperia/dergo/`. Polylang may omit the language prefix from CLI permalink output; verify actual HTTP redirects and canonicals.
8. Run an end-to-end synthetic submission with a harmless attachment and confirm receipt in valon@dua.com. Check the private inbox, file access and notification state. A mocked mail call or provider acceptance alone does not prove inbox delivery.
9. Verify WP cron is invoked by the host's independent scheduler. Delivery is deferred to `valon_tip_deliver`; disconnected sending reschedules hourly and failed attempts retry every 15 minutes, up to five attempts. Owner retry is available under Tools → Investigation inbox.

## Privacy and limits

Submissions are private custom-table records, not posts or public media. Owner-only downloads require a WordPress nonce. Files are stored under random names outside the web root with mode 0600. Content, filenames and contact data are escaped in the inbox. Review does not publish anything.

Up to 3 attachments, 5 MiB each and 10 MiB total; allowlisted MIME/extension combinations: PDF, TXT, JPG, PNG, WebP. Images must be decodable and at most 40 megapixels. Files are unscanned reader material and should be treated accordingly. Name and email are optional; the form does not promise anonymity. Removal requests use the receipt code; owner deletion is currently a manual administrative operation.

Signed short-lived form tokens, same-origin checks, a honeypot, duplicate-retry receipts, serialized writes, storage reserve and rate limits protect submission handling. Per-address rates use REMOTE_ADDR, not untrusted forwarding headers; confirm proxy behavior before opening the endpoint broadly. The rate is 8/hour/address and 50/hour globally.

Email status `accepted` means wp_mail returned success, not confirmed delivery. `failed` retains the entire submission and attachments. A worker crash in `sending` requires owner review before a manual retry to avoid duplicate email.

## Validation recorded in protected staging

Synthetic multipart upload, private permissions and storage location, duplicate receipt retries, forged token rejection, cross-origin rejection, required consent, honeypot, disguised file rejection and anonymous download denial passed. Mocked mail confirmed recipient and attachment paths; simulated delivery failure retained the saved submission. No real mail was sent by staging.

Browser QA must cover both languages, desktop/mobile, the timeline filters, network profiles and evidence selectors. Locale data is an external local script so consent-plugin keyword scanning cannot misclassify source-reference strings as tracking code. Static option values are preserved before translating their labels.
