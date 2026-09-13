# Newsletter landing page and video article drafts — 13 September 2026

Theme 2.4.0 and Valon Platform 1.2.0.

## Delivered behavior

- Existing newsletter routes become focused portrait-led landing pages: `/newsletter/`, `/sq/letrat/`, `/de/briefe/`.
- All new public copy has English, Albanian and Swiss Standard German editions. The offer is the free fortnightly newsletter. No ebook/download promise is displayed.
- Navigation is reduced to the personal identity and real language alternatives on this landing page; the writing archive and footer destinations remain accessible.
- The current Mailchimp hosted-form fallback is retained. The provider form is English; its language limitation is stated at the handoff. Sending is paused by the account's allowance and has not been resolved by this release. No test emails or campaigns were sent.
- New video articles render one official TikTok player immediately in their HTML, retain a source link and label the original Albanian recording. Editorial text can be translated without pretending the audio was translated.
- Each article has an owned editorial cover for cards and sharing. These portraits are not presented as video thumbnails in structured data.
- A Tools → Video article drafts importer accepts one to ten owner-sourced video groups, each with EN/SQ/DE prose. It requires administrator capability and a nonce, validates the full batch, sanitizes content, locks concurrent imports, and never overwrites existing editorial content. Every new edition remains a draft with the existing owner-approval guard.
- Source captions and review notes remain in the post editor. Original dates, duration and stable actual-video thumbnails still require verification. No incomplete or fabricated VideoObject schema is emitted.

## Editorial batch

Three videos, nine substantive drafts, stored privately in `review/content/video-articles.json` and WordPress. Draft prose is excluded from the public GitHub repository and release packages.

1. Starting a business with AI, content and community — TikTok `7597539248341847317`.
2. A passport does not build your courage — TikTok `7684727821083626753`.
3. Returning to Kosovo takes time — TikTok `7684259488060214529`.

Sources are the original captions verified during the social audit. The full recordings have not been transcribed. A fresh TikTok detail-page check encountered a CAPTCHA; it was not solved or bypassed. The articles are explicitly caption-based editorial drafts for owner review.

## Verification

- 28 new WordPress integration assertions: source restrictions, three languages, spelling, anonymous denial, real drafts, language relationships, iframe validation, covers, approval blocking, idempotency, preserving edits, concurrent import and interrupted group recovery.
- 54 existing social/newsletter integration assertions passed with isolated HTTP mocks, without sending emails.
- 262 local HTTP/SEO assertions passed.
- Theme and plugin release ZIPs built successfully.

## Deferred by the owner

Ebook content and delivery, TikTok API authorization and automatic historical import. Mailchimp account sending allowance remains a separate dependency. New articles require approval before public publication.

## Rollback

Revert this release's Git merge and update the existing theme/plugin through WP Pusher. The new draft posts can remain private; existing public article prose and legacy URLs are unchanged. No destructive migration is performed.

## Production verification and preview polish

- Theme 2.4.1 keeps draft-language navigation within the actual translated previews for authorized editors; anonymous visitors do not receive draft links.
- Video pages use a localized newsletter destination after the article. YARPP's supported `noyarpp` filter suppresses its unrelated or English fallback block only on these video articles. The regular article archive is unchanged.
- Nine production drafts: 2443–2451, arranged EN/SQ/DE for each of the three sources above. All were verified as draft in Tools → Video article drafts.
- The first actual TikTok embed loaded its original cover/caption and played (153.6 seconds total duration). Article prose is still caption-based; playback verification is not a transcript review.
- All three landing pages were inspected in 390px iframe viewports as well as desktop. Browser-level viewport overrides were ineffective and were reset; the actual 390px layouts were checked in the local review harness.
- 31 draft-workflow checks now pass, including preview access control and suppressing unlocalized related content.
- Production HTTP/SEO audit: 266 of 266 passed after the main release.
