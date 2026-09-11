# Measurement contract

Use the existing GA4 property/tag after validating consent behavior. This theme installs no duplicate GA tag.

The existing consent manager should set `window.valonAnalyticsConsent = true` only after the relevant analytics permission is granted. Until then, the plugin sends no events. Attach the existing gtag loader through the current site consent integration; credentials and API keys never belong in it.

| Event | Trigger | Bounded parameters | Meaning |
|---|---|---|---|
| newsletter_submit | Successful accepted form response | language, placement, source | A request, including an already-known address. Not a confirmed subscriber. |
| article_engaged | At least 30 visible seconds and 75% article progress | article_id, language | A reading signal, not proof of comprehension. |
| social_post_open | Visitor requests a social player | platform | Player requested, not confirmed play completion. |
| social_source_click | Visitor follows the original post | platform | Outbound source click. |
| page_not_found | Rendered 404 page | page_path without query | Broken journey to investigate. |

Never add email, Mailchimp member hash, IP address, token, raw form data or free text as analytics parameters. The public response contains a localized generic message only.

UTM convention:

```text
?utm_source=tiktok&utm_medium=organic_social&utm_campaign=personal_brand&utm_content=bio_sq
?utm_source=linkedin&utm_medium=organic_social&utm_campaign=letter_01&utm_content=post_en
?utm_source=newsletter&utm_medium=email&utm_campaign=letter_01&utm_content=read_en
```

The form carries only an allowlisted source category into Mailchimp VSOURCE, with explicit VLANG. Detailed campaign attribution stays in consented GA4. Do not claim exact campaign-level confirmed-subscription matching without a separately verified lawful implementation.

Monthly report by source and language:

- New confirmed subscribers and unsubscribes from Mailchimp, using confirmed status/date and VLANG/VSOURCE.
- Attributed signup request rate = accepted form requests / eligible landing sessions; distinguish this from confirmed signup rate.
- Confirmed signup rate by source = newly confirmed members with that source / attributable eligible sessions over a matching window. Record cross-device, delay and consent limitations.
- GA4 engaged sessions and article_engaged counts, with sessions/users/views distinguished.
- Search Console organic landing-page clicks/impressions, query mix and indexability.
- Newsletter clicks and replies. Opens are a secondary, privacy-affected signal.

Use consistent periods and record report timezone, filters and definitions. Do not combine social video views, impressions, profile visits and GA sessions into a measured funnel. Establish the real baseline before forecasting growth.

Search Console access, Mailchimp access, consent integration and actual subscription confirmation remain launch dependencies.
