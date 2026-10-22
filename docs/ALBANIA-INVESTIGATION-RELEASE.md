# Albania investigation release — 22 October 2026

Public route: `/albania-protests-investigation/`. Publication is a separate WordPress page action; the theme does not automatically publish content.

Source: supplied `albania_nzz_investigation.html`, 64,615 bytes, plus `docs/research-tirana-protests-1936-italian-warning.md`. The full original sections are retained, including the sections appended outside the original HTML document. Added the historical-context chapter, direct network source links, keyboard relationship selection, static story/timeline/source fallbacks and explicit research gaps. Leaflet 1.9.4 and vis-network 9.1.9 are bundled with licenses. OpenStreetMap tiles load only after the reader requests the map.

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
