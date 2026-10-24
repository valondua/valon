# Albania investigation release — 22 October 2026

Public route: `/albania-protests-investigation/`. Publication is a separate WordPress page action; the theme does not automatically publish content.

Source: supplied original investigation HTML, 64,615 bytes, plus `docs/research-tirana-protests-1936-italian-warning.md`. The full original sections are retained, including the sections appended outside the original HTML document. Added the historical-context chapter, direct network source links, keyboard relationship selection, static story/timeline/source fallbacks and explicit research gaps. Leaflet 1.9.4 and vis-network 9.1.9 are bundled with licenses. OpenStreetMap tiles load only after the reader requests the map.

## Attribution checks and limits

- The official 24 October 2022 transcript contains the Italian-informant quotation. The separate 25 October gala transcript is not the source for it. The original 1936 archival document remains unverified.
- Corrected the brief's claim that Rama explicitly said Jewish arrivals founded Orikum/Phoinike: the official passage instead discusses an arrival narrative and place names. Phoinike's earlier chronology is supported by De Maria's archaeological study.
- Bektashi sovereignty remains a proposal in the cited reporting; the Utrecht authors' constitutional conclusions are attributed scholarly analysis, not a judgment.
- Government statements about Sazan and Zvërnec are dated and attributed. Title, investment status, permits and sovereignty are distinct. No group-based territorial or protest-financing theory is treated as proven.
- Reuters' Balluku report was checked through its MarketScreener syndication; denial and presumption of innocence remain visible.
- Checked the World Bank country-page GDP figures and AADF's $6.5m museum commitment.
- Yacht-host identity differs between reports; it is not asserted as an established identity in the graph.
- Media coordinates remain illustrative annotations, not measurements of outlet-wide bias. Historical ownership records need current registry checks. Pending network citations are explicitly research leads. Not every external document or claim has been independently verified.

## Deployment and rollback

Follow `DEPLOYMENT.md`. This release adds one template, one static content fragment and page-scoped assets; it does not update plugins, change runtime configuration or migrate existing published content. The existing Valon theme files were compared with repository files before release and matched.

A fresh private database backup was restored into a separate database; all 26 tables matched row counts and SHA-256 row-set digests. A full WordPress archive and host configuration archive were created outside the public root. Restored 15,345 regular files including 3,262 media files matched production hashes. Detailed private manifests remain on the existing Railway host, outside the web root.

Staging is a restored copy on the same host, bound exclusively to loopback, with a separate database, noindex, cron disabled, blocked outgoing WordPress HTTP/email, scrubbed sending integrations and an empty process environment. No public staging hostname or live-data import is used.

Rollback the new page to draft and restore the saved theme release if necessary; do not restore the entire database over newer production content. Keep the private database/media/configuration backups for recovery. This backup is on the host volume and is not an off-site disaster-recovery guarantee.

## Research expansion — 23 October 2026

The 16 research commits ending at `dd46461ae72602351ba3d0220b5004457c573e2f` are integrated as 44 editorial entries in the public page, with source links, dated evidence grades, limitations and a public-record research queue. This is a reviewed synthesis, not publication of private submissions or draft outreach. Existing 13 sections and their interactive assets remain present. The homepage and three-language navigation introduced by PR #45 remain unchanged.

Coverage of the seven source files:

| Research file | Public entries |
| --- | --- |
| `albania-evidence-network-2026.md` | R01–R07, R14–R20, R44: procurement, historical beneficial owners, Sazan/Zvërnec, Durrës, honours, foreign interference, MEK, national-interest questions |
| `research-tirana-protests-1936-italian-warning.md` | Existing historical chapter plus R08–R13: architecture, institutional reports, WikiLeaks/OSCE/ICIJ |
| `albania-israel-business-investment-network-2026.md` | R21–R29: corporate and diplomatic register, project vs ownership distinctions |
| `albania-organized-crime-global-networks.md` | R30–R34: international operations, separate legal cases and enforcement progress |
| `albania-soros-networks-and-financial-trails.md` | R35, R38, R40: institutions, competing funding accounts and separate procurement cases |
| `albania-osf-grants-vs-public-contracts-ledger.md` | R36–R40: checked filing entries, unreconciled totals, Treasury snapshot and procurement measures |
| `albania-community-notes.md` | R17, R27, R41–R43: atomic claim checks; no private sender identities or screenshots |

### Source review and substantive corrections

129 distinct original URLs were attempted. The initial retrieval had 27 failures; some successful responses were empty, JavaScript shells or redirects. Selected records were checked through browser/search retrieval and official alternatives. HTTP success is not a verification grade. The register explicitly preserves unresolved originals, current-ownership gaps, dated court reports and unavailable material instead of claiming comprehensive verification.

- UN roll-call A/RES/78/282, 23 May 2024: Albania **YES**, Serbia **NO**, Israel non-voting. Replaced the draft's unchecked non-voting claim.
- US State Department initial MEK offer: corrected `/206319.htm` to `/206362.htm`, 18 March 2013, Berisha's offer for 210 residents. The 2022 FBI/CISA cyber attribution is not proof of protest organisation in 2026.
- AIDA PDF is the 2025 information-request/response register, not a complete strategic-investment register. It references Decision 23/2024; annexes and complete beneficial ownership remain outstanding.
- The downloaded World Bank brief identifies itself as **October 2026**, with model estimates 25.0% (2024), 23.1% (2025), projection 21.6% (2026), at $8.30/day in 2021 PPP. Replaces draft 23.8%/October 2025; survey vs estimate distinction is explicit.
- 2024 Form 990-PF, EIN 26-3753801, Part XIV 3(a), PDF page 12, contains two OSFA general-support paid entries: $2,560,000 and $3,347,000 (sum $5,907,000). The draft's $26,535 2023 entry and multiyear grant-index aggregates were not corroborated; they are labelled accordingly and not added to the checked entries.
- TI's 5D case reports ALL 2bn in **contracts**, ALL 200m net profit. Neither measure equals Treasury payments. Overit's payee totals remain name-based, without verified NIPT/contract joins.
- Sherrie Westin was described as Sesame Workshop **President** at the June 2022 event; corrected the title in the source notes.
- WHO's dynamic indicator, the EBRD page and redirected CIA archive remain verification gaps. ACER's percentages are attributed summary figures pending denominator/questionnaire review. No detailed ECRI/Amnesty findings are invented.

### Implementation and checks

`assets/albania/research-register.json` is the reviewed content source. Regenerate its static HTML with `python3 scripts/build-albania-research.py`. The WordPress template inserts that local fragment; all entry text and source links exist without JavaScript. The new script adds accent-insensitive multiword search, combined topic/evidence filters, live result counts, empty state, reset and hash-linked disclosures. It adds no external runtime dependency or outbound request.

Staging uses the existing protected loopback WordPress clone and separate database. Changed live theme files were copied to the private `research-before-20261023` recovery folder; restore copies were compared against live hashes. The prior full DB/media/config backup and restoration evidence remain available on the host volume (not an off-site backup).

Checks: PHP syntax on staging; JS syntax; unique IDs and valid internal anchors; all 13 original sections preserved; all 44 entries have limitations and HTTPS source links; staging noindex; desktop and 390px mobile layout with no horizontal overflow; keyboard disclosure; accent search, combined filters, empty state, reset and deep-link reveal; no-script copy retains 44 entries and working native disclosures; original graph selector, opt-in map, timeline filter, evidence search, media search, source search and chart switch. No browser console errors were observed during the interactive checks. This is functional/local rendering QA, not a field Core Web Vitals measurement.

Deployment: merge the reviewed branch to main, apply the theme using the existing WP Pusher installation as its web-service user, and verify each release-file hash plus the public rendered page. A merge alone is not evidence of deployment. Update only the investigation page's modified timestamp after deployment. Rollback restores the saved three previous files and removes the three newly introduced assets/fragments; do not restore the full production database over newer content.
