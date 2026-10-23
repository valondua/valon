# Chapter links and GitHub coverage review — 24 October 2026

The six visual previews and full story chapters now link to stable person/profile IDs and exact dated timeline records. Profile links select the corresponding network node and show its relationships and sources. Event links clear a conflicting timeline search, focus the event, and include a return link to the chapter. IDs are shared by the English and Albanian editions; labels are translated.

The chapter-six chronology summary is also present in the extended timeline, reusing the existing dated account, source and limitations. No new factual claims are introduced by that summary. Direct URL navigation waits until the other widgets have rendered, avoiding an incorrect scroll position on load.

## GitHub content coverage

Compared the current main branch and the original feature/albania-investigation-2026-10 branch. The original feature branch has no unmerged commits. Confirmed the following records in the public page, rather than assuming that repository files had been published:

- R30: Europol and Eurojust cross-border investigations.
- R31: Dritan Gjika allegations and asset orders.
- R32: Saimir Tahiri's historical reported judgment, offence distinction and denial.
- R33: Ahmetaj, Koka and Bllako, with separate case histories and legal-status limitations.
- R34: enforcement progress and remaining weaknesses in the European Commission report.

These cover the core organised-crime research in docs/albania-organized-crime-global-networks.md. A prominent bilingual entry point now links directly to these five records. Criminal allegations are not treated as convictions, and these sources do not establish Rama's direct participation in criminal organisations.

This is not a claim that every sentence or URL in every research note is public. Examples still confined to the Soros-network research note include specific OSFA/Western Balkans leadership names, the OSFA registry identifier, named consortium partners, and the exact alleged €491,000 benefit figure. They require separate verification and editorial decisions before expansion. Alternative source URLs and mirrors also differ between the notes and published record.

## Verification

Protected staging: all six chapter-to-person and chapter-to-event journeys exercised in English and Albanian at a 390px browser viewport. Tested hidden-event search recovery, repeated same-hash links, direct event reload, research record expansion, and research-filter reset. Targets settle below the sticky header; no horizontal overflow observed. Native fragment links and static source content remain present without JavaScript. Syntax, duplicate-ID and fragment-target checks passed. These are browser simulations, not physical-device tests.

Before-images for the six changed production files are held outside the webroot in the existing private release backup area. No database, mail-delivery, or submission-storage changes.
