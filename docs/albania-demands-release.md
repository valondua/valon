# Visible demands and bilingual accuracy

This scoped release builds on merged PRs #56–#58 and #60. The demands section appears immediately after the opening image and before research graphs, with a hero CTA, a persistent masthead link, and the same `#demands` fragment in both languages. Native links and server-rendered text work without JavaScript. JavaScript transfers focus, measures the sticky masthead offset, and preserves section/record fragments when switching languages.

## Source review

- [Boldnews, 4 June 2026](https://boldnews.al/2026/06/04/protestuesit-paraqesin-5-kerkesa-te-panegociueshme-per-ramen/): rally organisers' platform. Repeal of strategic-investor status/framework and the Mountain Package is distinct from reversal of amendments to protection/heritage laws.
- [Lapsi, 22 June 2026](https://lapsi.al/protesta-e-qytetareve-shpall-5-kerkesat-drejtuar-rames-i1088427) and [Ora News, 26 June 2026](https://www.oranews.tv/newsmobile/1332353/): coordinating-group platform and later publicised letter. Nonpartisan technical transitional government with a 12-month mandate; lifetime political-career limit of two full or partial PM terms. Not a claim that every protester agreed.
- [OBCT, 14 October 2026](https://www.balcanicaucaso.org/en/cp_article/albania-students-protest-escalates-into-violence/): interviews at the 13 October student protest; individual grievances, not a unified student manifesto.

All four linked texts were read for this release. Cards label paraphrases, issuers, event/platform dates, reporting dates, sources and implementation status. Implementation is not independently verified; no claim of unanswered/unmet demands is made.

## Verification and release scope

Eight Node regression checks cover bilingual semantic mappings, score/rank units, preservation of allegation/denial, dictionary parity, R44 source/render agreement, visible native demands anchors, translation coverage and fragment/focus behavior. JavaScript syntax and diff whitespace checks pass. PHP template syntax passes on the existing host.

Protected WordPress staging returns both editions with `noindex, nofollow`, mail/HTTP blocking and loopback-only binding. Shared staging was found to be in active use; final validation uses a separate root-private copy at `/data/private-albania-demands-stage`. Browser checks use those WordPress-rendered pages mirrored locally with repository assets. This is a rendered-page check, not a production-host network or physical-device test. Desktop and 390px mobile checks cover native/keyboard navigation, focus, sticky-header clearance, no overflow and language switching of `#demands` and `#r33`. No-JavaScript captures verify that translated content is present before browser scripts execute.

Production rollout must compare the seven existing target files with the checked main revision immediately before installation, back them up outside the webroot, install the nine scoped files, and verify live EN/SQ content/navigation. No form implementation, authentication, database, media or infrastructure changes are included. No email or submission is sent. Privacy/security/access guarantees are not certified.
