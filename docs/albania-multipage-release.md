# Albania dossier: multipage release

The former landing page required roughly 15,610 words of visible reading in a desktop browser (about 67,721 px high), with 16 navigation links and six separate search controls. A separate raw-text extraction counted about 25,494 words including repeated content. Those are inspection measurements, not analytics or reading-time observations.

## Structure

Six primary destinations: overview, demands, timeline, investigations, sources and online coverage. Five substantial investigation pages retain coastal development (including the original six chapters), accountability, media, the single connected Jewish history/statements/museums module, and development/context. Submit information remains a utility link.

EN routes use `/albania/`; SQ routes use `/sq/shqiperia/`. `includes/albania-dossier.php` is the route and section mapping. `/online-coverage/` avoids WordPress's reserved `feed` slug. The underlying source fragments and 55 canonical records are preserved. The landing page includes orientation, demands, five dates, three dated reporting cards and links to further research.

Original section/record fragments are mapped client-side to canonical child pages. The overview also provides server-rendered `noscript` fragment targets and links, because fragments are not sent to the server. Native links, breadcrumbs and the language switch work without JavaScript; JavaScript additionally preserves the selected fragment and focuses the target. Sources remain next to claims. The source library combines canonical records with a document catalog; it does not turn allegations into facts.

## Feed and images

Six manually reviewed source cards: Boldnews June 4, Ora June 26 about the June 22 platform, bne July 11, OBCT October 14 about October 13, the April 7 government transcript of the April 6 ceremony, and a clearly limited Reddit discussion lead with no invented publication date. No automatic ingestion, popularity ranking, field-footage claim or autoplay embed. Search/type/language filters and finite load-more enhancement; all cards remain present without JavaScript.

Two June 13, 2026 photographs by Albinfo, CC BY 4.0, downloaded from verified Wikimedia Commons originals. Source pages, author and license links are in the captions. Resized without cropping; no endorsement implied. Hero variants: 600 and 1200 px; event image: 960 px. No synthetic protest imagery.

## Release control

Use the isolated `/data/private-albania-multipage-stage` and its separate `mp20261024_` table prefix. No mail/external HTTP from stage; loopback rendering and noindex guards retained. Candidate code remains in legacy mode until the idempotent `tools/albania-dossier-pages.php` has created and paired all 20 children and sets `valon_dossier_multipage_ready`. This prevents activation with missing destinations. The script rejects conflicting unrelated routes and preserves existing page content. It creates drafts first and publishes the known children after all pairs exist.

Before publication reconcile remote main and runtime hashes, preserve unrelated changes, back up changed runtime files and affected page/options metadata outside the web root, then use the established publisher. Verify 22 direct routes, canonical links, language pairs, all 55 record anchors, the 19-card history module and representative browser journeys. Rollback restores scoped runtime files and the previous activation option; newly created children can be returned to drafts. Do not modify authentication, forms or infrastructure.

## Validation

Editorial regression suite and multipage structural/translation tests run with `node --test tools/albania-accuracy.test.cjs tools/albania-jewish-history.test.cjs tools/albania-dossier.test.cjs`. Protected PHP rendering and desktop/mobile/no-JavaScript checks are additional release gates. Optional video playback was not verified and no player is certified or embedded.
