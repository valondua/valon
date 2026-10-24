# Albania investigation images and individual voices — 24 October 2026

Scope: five context photographs on the shared investigation cards and three individual speech/interview cards below the existing demands. No changes to demand platforms, evidence records, submission handling, authentication or infrastructure.

## Source and content review

- Samanta Toçka: BalkanWeb article published 20 October 2026 at 07:59 identifies a 22-year-old student and transcribes concerns about emigration/family separation, unequal opportunities and courses closing. Its specific Rumble player is `v7ey0zo`; the visible title matches and actual playback advanced with the studio interview frames. This is an edited studio excerpt, not authenticated footage of the 13 October rally. The recording date is unknown. No checked caption track or complete independent transcription is claimed. Summary follows the attributed publisher transcript.
- Taulanda Jupi: BalkanWeb/News24 article published 20 October 2026 at 10:30. Matching verified News24 Albania video `x2nXzaHIBSI` shows her interview at the protest. Playback and the available Albanian auto-caption transcript were inspected: student solidarity, preventing further violence and parliamentary responsibility support the concise paraphrase. University of Arts lists her as a lecturer. Euronews’s 8 October 2023 interview identifies her as a Democratic Party leadership member; this dated affiliation is disclosed without claiming a current party office. She is not presented as representing all teachers.
- Fatos Lubonja: Report TV’s 7 June 2026 report, 19:13 entry, updated 8 June, links `iqHe3iky0QM`. Matching verified Report TV channel, rally frames, playback and Albanian auto-caption transcript inspected. His calls for scrutiny of Zvërnec, transparent SPAK investigation and resignation are framed as individual political speech. Criminal allegations are not repeated as findings.

Publisher reports and original video links remain next to each summary. Auto-generated captions are not certified transcripts. No video, broadcaster still or full transcript is copied to the site. Players are loaded only by a user action; YouTube uses its privacy-enhanced domain. Rumble may serve ads. Platform availability remains external and source links remain available even with JavaScript disabled. No invented timecodes, recording dates or rights claims.

## Images

The user’s Library screenshot was materialized with the supported Library helper and inspected locally. All five selected images were visually inspected. Exact Commons originals, authors, dates and derivative licences are in `assets/albania/images/CREDITS.md`. The 480/960px derivatives preserve full composition inside equal 3:2 frames. Captions identify historical photograph dates and context. No synthetic evidence or implied portrait allegation.

## Verification

Initial code regression: 34 tests pass, including initial translated graph labels, source search resets, content invariants, and new click-to-load/close/focus/player allowlist tests. Staging, responsive rendering, real embedded playback, content preservation, rollback and live checks must complete before release is described as verified.

## Staging milestone

- Reconciled PR #70 (Law 21/2024 explainer) and reran **38 passing tests**.
- Protected WordPress stage rendered all **22 routes** with 200 responses, noindex protection, six navigation links, all 55 canonical records, 19 history cards, unique IDs and valid mapped targets. Graph script order remains correct in SQ.
- Desktop and 390px mobile rendering inspected. All five local image assets loaded with translated alt text, consistent 3:2 frames and no horizontal overflow. Credit disclosure and source/licence links remain native HTML.
- New `#protest-voices` / `#voice-*` fragments migrate from the overview and language switching preserves them. Keyboard load, iframe focus, close and focus return tested.
- Script-stripped server-rendered fixture retains all three original-video links and summaries, with no visible inert load buttons. This is a no-dossier-JavaScript fixture, not a browser-wide script-disable setting.
- Rumble `v7ey0zo` played inside the staged dossier (elapsed time advanced to 15.8s). Both YouTube originals played; News24’s own embedded `x2nXzaHIBSI` also advanced to 6.8s. Privacy-enhanced embeds in the localhost preview returned blank/connection-closed or unavailable states, while the same source page’s normal YouTube embed played. Do not describe localhost YouTube playback as verified. Public-origin playback must be checked after release; original links are always available. Player status says requested, not successfully loaded.
- Rollback rehearsal: archived current production theme under `/data/private-albania-media-release-20261024/theme-before.tar.gz`; restored that archive only into private staging, rendered both baseline demands pages, then restored candidate and verified both translated voice sections. Production was not switched during the rehearsal. No database/page migration is needed for this update.
