# Social videos → searchable pages → email subscribers

Research and implementation brief · 13 September 2026

## Recommended approach

Use TikTok as the primary source for Valon’s Albanian videos. Create one **video-first WordPress article per distinct idea/video**, with the TikTok player near the top, useful edited writing below it, and one relevant ebook invitation. Use English, Albanian and Swiss Standard German editions of that article, linked as translations. Connect Instagram/Facebook crossposts to the same editorial record instead of duplicating pages for each platform.

Every supported, owned public video can enter the **draft queue**. Publication remains a separate editorial decision with Valon’s approval. A reply that only makes sense with missing context, a duplicate, a deleted/private video or a caption with no substantive idea should stay in the queue until it can support a useful page. Do not auto-publish hundreds of expanded captions.

## What the current profiles show

Observed directly in the signed-in browser on 13 September; this is a profile/content sample, not a complete analytics export.

| Platform | Observation | Role |
|---|---|---|
| TikTok | 36.6K followers; substantial Albanian video/reply catalogue. Pinned business video discusses AI, content and community. | Primary source for video articles; invite comments and following on TikTok. |
| Instagram | 18,350 followers in the accessible label; 456 posts. Recent passport/mindset, Kosovo and social-media posts overlap with TikTok. Bio still leads to bethe.one. | Crosspost reference; stories, personal connection and ebook distribution. |
| Facebook | 145K followers. Latest visible passport/potential caption also appears on Instagram/TikTok. | Distribution to the largest visible existing audience; support text posts too. |
| LinkedIn | 35,845 followers. Featured posts cover acting now, passion, entrepreneurship and personal reflections. About copy still describes older company stages. | Founder/technology articles and professional connections. |
| X | 425 followers. Bio links to dua.com; mix of founder, personal growth and Albanian video content. | Supporting conversation and distribution. |

Follower counts overlap; they are not a count of unique people or website traffic. These observations do not establish conversion rates.

## Search eligibility: article search and video search differ

A substantive article can appear in normal text results with an embedded video. For Google’s video features, the page should primarily exist to watch **one prominent video**, with a unique title/description, an indexable page and a stable accessible thumbnail. Third-party embedded videos can be indexed on both the creator’s site and the hosting platform, but neither inclusion nor ranking is guaranteed. Google must discover the player without clicking a button. A page may be indexed while its video is not. [Google video best practices](https://developers.google.com/search/docs/appearance/video)

This changes one part of the earlier plan: click-to-load players remain appropriate on the homepage, Media and Watch grids. The individual video page needs a discoverable player in its rendered page. If a consent layer prevents that, keep the written article crawlable and test video eligibility; do not show crawlers a different experience. If TikTok prevents Google from fetching the video, use an owned original recording on a suitable video host for a later pilot rather than promising that TikTok alone will produce video results.

Add `VideoObject` with a verified name, description, actual-video thumbnail, original upload date, duration when known and the official player `embedUrl`. Use `contentUrl` only for the actual media file, not the TikTok web page. Link the video to the page and author identity; merge with Yoast’s graph instead of competing schema blocks. Validate with Rich Results Test and Search Console. [Google VideoObject documentation](https://developers.google.com/search/docs/appearance/structured-data/video)

## Page specification

1. Language switch and breadcrumb, then a clear localized title.
2. A short introduction describing the actual idea.
3. One large TikTok embed with attribution, original audio-language label and a permanent “Watch/comment on TikTok” link.
4. Original caption, with separately identified reviewed caption translations.
5. Edited article: the central point, context from the recording/notes, practical implications and a useful next step. No arbitrary word target.
6. Reviewed transcript when available. Translated text must not be presented as translated audio or subtitles.
7. Ebook invitation matching the topic, followed by two relevant articles. Keep newsletter signup the primary conversion; TikTok is a secondary link.
8. Original source date and editorial publication date remain distinct. Translations get self-canonicals and reciprocal `en`, `sq`, `de-CH` alternates only when published.

A source caption alone can support a short faithful note. It cannot reliably support a detailed transcript, fabricated examples, health claims or a long first-person essay. AI-assisted editing is acceptable; scaling unoriginal, low-value pages to manipulate ranking is not the strategy. [Google’s spam policies](https://developers.google.com/search/docs/essentials/spam-policies#scaled-content)

## Three concrete pilot candidates

| Verified TikTok source | Faithful article direction | Source limits |
|---|---|---|
| [Starting a business in 2026](https://www.tiktok.com/@valon_asani/video/7597539248341847317) | **EN:** Starting a business with AI, content and community · **SQ:** Me nisë biznes me AI, përmbajtje dhe komunitet · **DE:** Mit KI, Inhalten und einer Community ein Unternehmen starten | Caption explicitly identifies these three ideas. The recording/notes are needed for examples and detailed instructions. |
| [Passport and courage](https://www.tiktok.com/@valon_asani/video/7684727821083626753) | **EN:** A passport does not build your courage · **SQ:** Pasaporta nuk ta ndërton guximin · **DE:** Ein Pass macht dich nicht mutig | Caption discusses personal agency regardless of location. Do not convert it into immigration or legal advice. |
| [Adjusting after returning to Kosovo](https://www.tiktok.com/@valon_asani/video/7684259488060214529) | **EN:** Returning to Kosovo takes time · **SQ:** Kthimi në Kosovë lyp kohë · **DE:** Im Kosovo wieder anzukommen braucht Zeit | Caption says Valon personally needed more than two years to settle. Keep that as his experience, not a universal claim. |

These are editorial proposals, not published articles. Their spoken recordings and full transcripts have not yet been reviewed.

## TikTok implementation

- Public selected-video embeds can start with verified URLs and TikTok’s official iframe player. No paid social aggregation widget is necessary for this pilot. Include controls, captions where TikTok provides them, original description and a visible source link. [Official TikTok player](https://developers.tiktok.com/docs/en/embed-player)
- Automatic catalogue backfill and new-video imports use authorized Display API `video.list`, pagination and stable external IDs. Access requires an approved developer app, Login Kit/API access, appropriate scopes and creator authorization. Being logged in to TikTok in Chrome does not grant the WordPress server API access. [Display API setup](https://developers.tiktok.com/docs/en/display-api-get-started), [video listing](https://developers.tiktok.com/docs/en/tiktok-api-v2-video-list)
- Store original caption, source URL, language, original timestamp, duration, availability, crosspost links and editorial translations. Keep the author’s supplied transcript/recording separate from embed-display metadata. API caption/title fields are not transcripts.
- TikTok cover-image URLs expire after six hours. Refresh feed covers via the API; do not put an expiring signed URL into permanent SEO metadata. Use a stable, owned thumbnail from the original video for each approved video article. [TikTok video object](https://developers.tiktok.com/docs/en/tiktok-api-v2-video-object)
- Imports upsert existing social records and create at most one draft per source/language. New imports never overwrite approved prose. Deleted/private sources hide from feed grids and flag linked articles for review; an independently useful article can remain with an honest unavailability notice and no stale video schema.
- Reconcile changed captions, duplicates, availability, token expiry and rate limits. Expose status in WordPress. The existing integration plugin already has the queue and core import/retry framework; real platform authorization remains pending.

## Ebook landing pages

Start with **one ebook offer in three languages**, not several unrelated PDFs. Give each language its own landing page and confirmed-delivery journey. Use campaign-specific links to that offer from relevant articles and social bios. Create a second topic-specific landing-page angle only if the actual PDF supports that promise.

The 11-page PDF is not present in this repository, and the public WordPress media endpoint returned no PDFs. Its title, content and source-language editions must be inspected before writing benefits or promising a download. The author has been asked for its title/location.

Proposed layout:

- Compact identity and EN/SQ/DE switch, without a large navigation menu.
- Actual ebook title and one concrete reader benefit.
- Cover and one or two real page previews; three benefits derived from the PDF.
- Email address and explicit language preference; no unnecessary name/phone fields.
- Button: **Get the free ebook / Merre ebook-un falas / Kostenloses E-Book erhalten**.
- Clear offer: the ebook plus Letters from Valon every two weeks, with unsubscribe information and privacy link. Do not silently add unrelated product audiences.
- Confirmation screen: **Check your email / Kontrollo emailin / Prüfe dein Postfach**.
- After confirmed opt-in: localized welcome/delivery email linking to the matching PDF edition. Existing subscribers also need a working way to receive the requested file, without receiving duplicate welcome sequences.
- Keep delivery pages out of search. A normal downloadable PDF link can be shared; this is a convenience gate, not DRM or a guarantee of secrecy.

Mailchimp documents file delivery through a welcome automation or final welcome email. Choose one, then test confirmation and actual delivery; do not enable both. [Mailchimp file delivery](https://mailchimp.com/help/send-a-file-to-new-subscribers/)

Mailchimp supports translated forms and response emails, but browser-language auto-translation is not a substitute for an explicit choice of the PDF/newsletter language. Swiss Standard German wording and the Albanian version should be reviewed manually. [Mailchimp form translation](https://mailchimp.com/help/translate-signup-forms-and-emails/)

**Current blockers to a live ebook offer:** unidentified PDF; no verified EN/SQ/DE PDF editions; Mailchimp still shows sending paused over its contact allowance (rechecked 13 September); server-side newsletter credentials and localized delivery are not yet configured. The existing live site links to the hosted English signup form. A successful form submission alone does not prove ebook delivery. Resolve these before advertising the offer.

## Pilot and measurement

1. Finish the Media/image improvements and retain all existing article URLs.
2. Review the PDF, prepare three localized landing pages and the delivery email; verify a real confirmed-signup-to-download journey.
3. Prepare the three pilot video articles from original recordings/notes and caption context, in all three languages, for Valon’s approval.
4. Publish approved pilots; check rendered players, title/canonical/hreflang, thumbnails, video structured data and mobile usability. Check Search Console indexing separately for pages and videos.
5. Connect the authorized TikTok importer and backfill the catalogue into drafts. Roll out useful approved pages in batches, using what the pilot reveals.

Track `ebook_offer_view`, `ebook_signup_submit`, **confirmed subscription**, delivery/link click, article engagement and `social_source_click`, with offer, source, campaign, page and language dimensions. Never send email addresses or subscriber identifiers to GA4. Confirmed new subscribers per eligible landing-page session is the acquisition conversion metric; returning subscribers requesting a PDF are a separate delivery metric. Attribute email confirmation with first-party campaign/offer fields carried through the provider, not by assuming an on-site submit event equals confirmation. Use unsubscribe and delivery failure rates as quality checks.

Suggested social destination after delivery is verified: the localized ebook landing page for ebook campaigns; `/start/` for the general personal-brand bio. Preserve direct company links for company campaigns. No social bios or posts were changed during this research.
