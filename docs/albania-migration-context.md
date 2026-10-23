# Population and migration source review — 24 October 2026

This release connects the census indicator and community claim R42 to three dated research records: UNFPA's demographic discussion (R52), Eurostat first residence permits (R53), and INSTAT's US diaspora tabulation (R54). The graph edges identify published statistical context, not individual culpability or a causal claim. Three dated timeline entries distinguish the 2024 discussion, the 2025 publication and the 2026 Eurostat source review.

## Primary sources and definitions

- UNFPA, 11 December 2024: https://albania.unfpa.org/en/news/%E2%80%9Cdepopulation%E2%80%9D-series-analyzes-factors-albanian-shrinking-population . Paraphrased discussion of emigration, fertility and ageing, and rights-based responses. Expert annual-emigration estimates and the article's broad diaspora totals are not imported as administrative counts.
- Eurostat `migr_resfirst`, retrieved 24 October 2026, updated 22 October 2026. Query: https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/migr_resfirst?lang=en&citizen=AL&reason=TOTAL&duration=TOTAL&unit=PER&sinceTimePeriod=2008 . Raw public JSON and derived annual CSV are in assets/albania/data/.
- Eurostat definition: https://ec.europa.eu/eurostat/cache/metadata/Annexes/migr_resind_esmsip2_an_4.pdf . A permit after a gap of at least six months can qualify as first again. Cumulative issuances are not unique emigrants.
- INSTAT, Diaspora e Shqipërisë në Shifra 2025, publication page dated 19 December 2025: https://www.instat.gov.al/sq/publikime/librat/2025/diaspora-e-shqiperise-ne-shifra-2025/ . PDF https://www.instat.gov.al/media/hnmh5ems/diaspora-ne-shifra.pdf , section 2.3, table 4, cites ACS 2023: 94,616 Albania-born; 111,904 Albanian ancestry born elsewhere; total 206,520. INSTAT's broad second-generation category explicitly includes people born in other Balkan countries. We attribute the tabulation to INSTAT, do not claim independently reproduced ACS microdata, and note missing uncertainty intervals.

## Reproducible EU calculation

Fixed geography is today's 27 EU countries: BE BG CZ DK DE EE IE EL ES FR HR IT CY LV LT LU HU MT NL AT PL PT RO SI SK FI SE. It does not represent changing historical membership. Exclude EU aggregate rows to avoid double counting; exclude UK, EFTA and Serbia. Sum only observed country-year values for 2008–2025. Croatia 2008–2012 and Luxembourg 2008 are missing; do not replace them with observed zeros. Sum = 1,056,487 reported permits, explicitly a lower bound. The EU27 aggregate is unavailable before 2013. Every derived annual sum from 2013 onward matches the published EU27 aggregate; 2025 = 68,653.

Secondary press totals of 999,138 through 2024 and approximately 1.08m through 2025 were not adopted: the current fixed-geography extraction does not reproduce them. No cumulative total is attributed wholly to one government, and no claim of deliberate forced emigration is upgraded.

## Release scope

English and Albanian share the numerical source data and include the caveats and source links. Census change, cumulative permits and US resident estimates are displayed separately and never summed. Existing PISA and 16-year GDP data remain unchanged. Application-only release: no database, media or mail changes. Before-images: /data/private-albania-form-release-20261024/migration-context-before. Existing restored full-backup evidence remains applicable; backups are on the same host, not offsite.

Validation: derived totals reconciled to every available EU27 annual aggregate (2013–2025); missing values retained explicitly. All original 51 research records and 33 timeline entries preserved; new totals 54 and 36. Unique HTML IDs, graph and timeline anchors, JavaScript syntax and whitespace checks passed. Protected staging rendered both languages with noindex/nofollow. Browser checks covered the EU graph detail and timeline link, Albanian US graph detail and all methodological caveats, and a 390px mobile reading view with no horizontal overflow. The persistent submit link points to the correct Albanian form; email configuration is unchanged. Final stage export also checks the translated UNFPA introduction.
