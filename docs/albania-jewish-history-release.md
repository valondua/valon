# Albania: statements, history and projects

Scoped bilingual section at `#jewish-history-projects`, reviewed 23 October 2026. The parent supplied the historical QA supplement and publication authorization. The dossier's 55 research records, demands CTA/navigation, map context and PR63 real-GDP work are retained.

## Editorial checks

- Selected public statements run from the government-published December 2015 i24news interview through May 2026. Event and publication dates are distinguished where known.
- March 4 AIPAC holiday hospitality is separate from the March 6 award speech. The latter's official English “reserve homeland for the Jewish people” and Albanian “një atdhe i dytë për hebrenjtë” are both retained, labelled as different government renderings; audio not checked. The combined excerpts total 12 words per language page, 24 across both.
- NLI P3-2550 is a file record containing the 1935 Elton report alongside other material. Catalog existence/title/date do not authenticate the speech's quotation or establish policy. No facsimile inspected.
- Sarandë archaeology, Ottoman migration and Holocaust rescue use separate historical sources. First-arrival legends are not archaeology; Yad Vashem's Arditi case prevents absolute no-deportation claims.
- Vlorë and Tirana are separate projects. USD 3m construction ceiling excluding VAT, USD 6.5m committed, the mayor's approximate EUR 9m and Rama's USD 200,000 restoration-design statement retain distinct scopes. None is actual paid expenditure.
- Council minutes (13 March 2024, pp. 6–8) distinguish cards 158/162, record 458m² and 41–0–0, and an already-held hearing. MCN's later hearing report does not establish first consultation after construction. Building identity does not resolve heritage impact or permits.
- News framing, named-speaker attribution, Rama's personal rationale and Hibrid's dated fact-check are labelled and linked. No new media embeds, photos, allegations or records.

## Implementation and validation

One static HTML section, exact-text SQ dictionary mappings shared by PHP and browser, scoped responsive CSS and native navigation. Both source excerpts preserve their `lang` attributes. Existing anchor logic now focuses the new section and preserves its fragment during language switching. Direct project links connect the award/speech cards to the project comparison.

`node --test tools/albania-accuracy.test.cjs tools/albania-jewish-history.test.cjs tools/albania-gdp.test.cjs`: 16 pass. JavaScript syntax and `git diff --check` pass. PHP lint passed in the existing Railway environment.

Protected stage: `/data/private-albania-jhp-stage`, isolated from concurrent publisher staging; inherited guard blocks mail and external WordPress HTTP and sends `noindex, nofollow`. Started only on remote loopback for capture. Production theme copied as baseline after six changed-existing-file hashes matched main, then seven scoped runtime files overlaid. No database edits, authentication changes or submission-form tests.

Browser QA uses captured WordPress HTML plus current local assets; third-party plugin assets may be unavailable in that preview. Desktop and 390px mobile show no overflow, focus below sticky navigation, translated content and preserved hash/quotes. Final live checks are required after release; local preview alone is not production verification.

Open documentary limits: original Elton report and speech audio; signed municipal reply and hearing records; heritage assessments/permits; contract awards/variations/payments; current opening and clinical outcomes. These remain explicit in the section.

Final staging checks: all rendered section text nodes exactly match EN/SQ source mappings, 19 cards and 55 existing records are present, PR63 CSV reference retained. Mobile native-anchor test on script-stripped SQ HTML: zero dossier scripts, no overflow, heading below header. Desktop keyboard Enter focuses the section; mobile selector reaches both demands and this section. EN→SQ switching retains the fragment. Direct speech-to-project links are present.
