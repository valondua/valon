# Remaining GitHub evidence review — 24 October 2026

Reviewed the remaining details identified in the GitHub coverage audit against the cited public sources. This release adds R45–R51, seven dated context entries, source links and graph relationships in English and Albanian. It retains the existing organised-crime records R30–R34, including Gjika, Tahiri and the separate incinerator case histories. It does not assert that every allegation in the working notes has been verified.

## Disposition of remaining material

| Record | Finding | Evidence and limitation |
| --- | --- | --- |
| R45 | OSFA identifier, listed formation date and legal representative | [Registry-derived listing](https://opencorporates.al/en/nipt/j62005011n); not a freshly certified QKB extract. Linked historical PDF was inaccessible. Registration is distinct from the foundation's stated 1992 programme start. |
| R46 | OSF Western Balkans leadership roster | [Organisation's roster](https://osfwb.org/who-we-are/), checked 24 October 2026. Check date is not appointment date. |
| R47 | Named historical LëvizAlbania consortium and 2023 transition | [Project account](https://www.levizalbania.al/en/about-us) and linked Swiss government material. The centre says it operates outside SDC auspices from 1 October 2023; historical funding must not be presented as an established current arrangement. |
| R48 | At least €491,000 alleged benefits involving Ahmetaj | [BIRN, 15 July 2023](https://www.reporter.al/2023/07/15/dosja-e-prokurorise-si-u-pagua-arben-ahmetaj-nga-biznesmenet-e-incineratoreve/) and Transparency International's case account. Allegation, denial and historical-status caveat appear together. One combined payer node avoids multiplying the alleged sum. No inference of Rama receiving money. |
| R49 | Exit's historical account of reform advocacy | [Exit, 22 August 2016](https://exit.al/en/constitutional-reform-conceived-planned-and-carried-out-by-soros/). Original strategy document was not independently accessible; the record supports what the outlet reported, not authentication or judicial control. |
| R50 | Official fiscal-risk reporting on PPP exposure | [Ministry of Finance, 2024 report published 2025](https://financa.gov.al/wp-content/uploads/2025/12/Fiscal-Risk-Statement-2024.pdf). Actual expenditure, plans and forecasts differ; units are thousand ALL. No invented project total or exact publication day. |
| R51 | CNA's report of opposition-released correspondence | [CNA, 11 December 2023](https://www.cna.al/english/aktualitet/dokumentet-firmat-e-rames-veliajt-dhe-agacit-per-aferen-me-incenerato-i383683). Letters attributed to Rama and Veliaj are not independently authenticated. The separate procurement record does not validate reproduced signatures. |

R37 is corrected: the philanthropy.org index does display the $4.7m/four-grant and $15.7m/ten-grant aggregates. Original annual-schedule reconciliation remains open; do not double-count overlapping verified 2024 entries. The separate $26,535 claim remains uncorroborated. R44 no longer contains the obsolete statement that the site has no submission service.

Other working-note leads retain existing limitations: further company/ownership claims (R28), historical leaked/offshore material (R13), technical attribution versus political interpretation (R15), and incomplete procurement/case records (R22, R44). Failed retrievals of an old Durrës registry URL, the CISA page, a Euronews Albania report, the original SPAK link, and the strategy-document viewer do not establish new facts. No unsourced claim of a unified criminal network, personal payment or judicial control was added.

## Implementation and verification

All new records are server-rendered and remain readable without JavaScript. The evidence graph supports multiple precisely labelled connections per record, including the three OSF Western Balkans leadership roles and three historical consortium members. Stable evidence links select the correct record and show its sources and limitations; timeline links retain stable IDs across languages. Existing chapter-to-person/event links and submission flows remain intact.

Local checks: 51 unique research records, 33 rendered chronological entries, unique IDs, translated new bodies/limits/edge labels, matching JSON/JavaScript data bundles, and JavaScript syntax. The data export now includes the previously rendered chapter-six chronology entry.

Protected staging, production verification and deployment identifier are recorded after release below. Before-images are saved outside the webroot in `/data/private-albania-form-release-20261024/remaining-evidence-before`. The existing full backup and separate-database restore rehearsal remain available; this change requires no database or upload migration. Roll back application files only, preserving any new private submissions.

Staging acceptance: all seven added records exercised through the evidence selector in each language; graph canvas renders without console errors. Tested timeline search reset on navigation, direct record expansion, bilingual source/denial text, desktop layout and 390px mobile overflow. Sticky submission links opened the correct English and Albanian form routes; no test email was sent for this content-only release. Both stage pages are noindex/nofollow. Full backup files and all nine before-images were confirmed present. A direct-link scroll conflict discovered during visual inspection was corrected before publication.
