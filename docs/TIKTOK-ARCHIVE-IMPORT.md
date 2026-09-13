# TikTok archive editorial imports

Valon Platform 1.2.1 accepts an optional `source_type` (`video` or `photo`) and `audio_language` (`en`, `sq`, `de` or `unknown`) on each manual article group. The exact source URL must match the owner, type and ID. Existing reviewed video batches retain their Albanian audio label. New archive batches should explicitly use `unknown` until the recording's language is verified; caption language alone does not prove audio language.

Video articles retain the official TikTok player. Photo articles use its image-gallery support, a photo-specific label and a link to the original photo post. Gallery music starts muted. TikTok documents image support in its [Embed Player documentation](https://developers.tiktok.com/docs/en/embed-player), checked on 13 September 2026. Owned editorial covers remain available to archive cards and sharing previews, without being presented as verified TikTok thumbnails. The source caption remains private editorial provenance. No VideoObject is emitted without verified video metadata.

All three editions are required. Imports remain idempotent and draft-only, preserve editor changes, and use the existing exact-content publication approval guard. A repeat import does not silently change source metadata or overwrite an article. Legacy URLs and approved articles are preserved.

The private catalogue under `review/catalogue/` records observed public owner post URLs and captions. It is excluded from Git and release ZIPs. Catalogue entries are not equivalent to finished articles: sparse captions require recordings, factual claims need checking, and crossposts should link to an existing article rather than generate duplicates.

Validation: `tools/video-article-qa.php` covers source type/URL agreement, owner restriction, audio labels, photo rendering, all three languages, draft privacy, approval enforcement and repeat-import recovery. The local suite passed 46 assertions.

Rollback: restore the prior Valon Platform release. No destructive database migration is used. Keep newly imported photo articles private if reverting to the older video-only renderer.
