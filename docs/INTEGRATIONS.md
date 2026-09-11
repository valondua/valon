# Integration setup

## Secrets and identity

Use the host's server-side environment or wp-config constants. Copy variable names from .env.example; never place secret values in the public repository, browser JavaScript, page content or analytics. Grant access only to Valon's intended accounts. The adapter checks the configured account ID against the authorized provider response before importing.

Refreshed tokens are AES-256-GCM encrypted in non-autoloaded WordPress options using the site's authentication salt. Rotating WordPress salts invalidates the stored token cache and requires reconnecting. Renewed tokens are preferred only while their original configured token fingerprint matches.

Set EXPIRES_AT from the provider's authorization response. Without it, proactive renewal cannot be calculated; TikTok/Instagram can still attempt one refresh after an authorization error. Token values themselves are not displayed in the admin status screen.

## TikTok

Obtain an approved developer app and creator authorization for user.info.basic and video.list. Configure expected open_id, access/refresh tokens, app credentials and expiry. The server verifies open_id, reads up to the most recent 100 supported public videos per hourly run and upserts by platform/external ID. Repeated windows deliberately refresh expiring thumbnail URLs. A query of up to 20 current videos checks explicit disappearance; a failed query preserves the cache.

Only video records appear in the homepage's TikTok slots. Photo posts are curated source links in Watch; they are not passed to unsupported video endpoints.

References: [Display API](https://developers.tiktok.com/doc/display-api-get-started/), [video list](https://developers.tiktok.com/doc/tiktok-api-v2-video-list/), [token management](https://developers.tiktok.com/doc/oauth-user-access-token-management/), [official player](https://developers.tiktok.com/doc/embed-player/).

## Instagram

Connect Valon's professional account through the Instagram API with Instagram Login and the required approved basic-read access. Use an eligible long-lived token and its actual expiry. Configure the expected Instagram account ID. A refresh attempt is made before known expiry; provider failure is reported, with the existing feed preserved.

The adapter reads up to 200 recent media records per run, including supported Reels/images/carousels, preserving captions and original publication time. New API fields/permissions must be checked during account setup against the actual approved app. Current requests use Graph API v26.0.

Reference: [Instagram with Instagram Login](https://developers.facebook.com/documentation/instagram-platform/instagram-api-with-instagram-login). Display embeds are separate from article drafting; [oEmbed data is for display](https://developers.facebook.com/documentation/instagram-platform/oembed).

## Facebook Page

Authorize the intended Page and approved Pages read permissions, then configure its Page ID and Page access token. The adapter reads up to 200 recent Page posts, verifies their author and excludes shared-story reposts and posts from other authors. Text posts remain first-class items.

Page tokens that require fresh user authorization are reported as reconnect-required; the plugin does not promise an impossible unattended renewal. Verify the supplied token's lifecycle in the actual app.

Explicit per-object 404/410 responses and successful Facebook responses confirming an unpublished, hidden or restricted-privacy state hide the item. Auth errors, rate limits and ambiguous permission failures never erase cached items. Admin Hide handles an explicitly confirmed private/deleted item while a platform gives an ambiguous response.

Reference: [Pages API](https://developers.facebook.com/docs/pages-api/posts/).

## LinkedIn and X

Add an own public post in Social queue → Connections & review, confirming ownership and original publication date. LinkedIn activity/URN links resolve to its official embed. X loads its official widget only after interaction. No paid X API or restricted LinkedIn personal-post-read dependency is introduced. Unsupported/private posts retain their source links.

## Background execution and review

Set DISABLE_WP_CRON=true. Run `wp cron event run --due-now --path=/path/to/wordpress` every minute using the hosting scheduler. The plugin's actual import event is hourly. Docker Compose provides this independently of page visits for local development.

Backoff starts at five minutes and caps at six hours; 429 Retry-After is respected within that bound. The next hourly scheduler opportunity executes an eligible retry. Platform and record locks prevent overlapping imports. Cache remains readable during outages. Recent-window sync is not a full historical social archive.

Admin tools: hide, feature badge, source language, related article, create draft, connection status and manual sync. Crossposted records can point to one editorial article. The homepage uses recent TikTok videos, with an Instagram fallback; Watch separates platforms.

No transcript is inferred from embed metadata. Draft generation only creates a source link and brief requesting an original recording, reviewed transcript or notes.

## Mailchimp

Use the existing personal audience after verifying ownership, subscription permissions, billing/features, sender identity and required fields. Do not import product audiences.

Create text merge fields **VLANG** (en/sq) and **VSOURCE** (website, tiktok, instagram, facebook, linkedin, x, newsletter). They capture an explicit preference and fixed source category atomically with a new pending member. Additional audience-required fields must be made optional or deliberately included before launch.

The form creates a **pending** member and never silently edits/resubscribes an existing contact. Existing-member responses remain generic to avoid exposing subscription status. Preferences and resubscription use Mailchimp's secure email links.

Albanian is not in Mailchimp's automatic form-translation list. Use the privately supplied reviewed bilingual confirmation copy, explicit VLANG segmentation and separate approved welcome/newsletter editions. Test actual Mailchimp response-email behavior: storing VLANG alone does not localize a confirmation message.

Connect the API key and audience ID. Complete sender authentication and an authorized two-language end-to-end test: submit → pending → confirmation delivery → confirmed member → correct welcome edition → preference change → unsubscribe. No real email was sent during development.

The endpoint uses same-origin checks, a honeypot and a salted-IP hourly limit. Ensure REMOTE_ADDR contains the actual client IP through a trusted hosting proxy configuration; do not trust arbitrary forwarded headers in PHP. Enforce an edge rate limit for a public launch.

References: [add member](https://mailchimp.com/developer/marketing/api/list-members/add-member-to-list/), [form translations](https://mailchimp.com/help/translate-signup-forms-and-emails/), [explicit language preferences](https://mailchimp.com/help/manage-international-subscribers-in-mailchimp/).
