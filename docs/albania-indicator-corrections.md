# Indicator correction — 24 October 2026

The GDP chart previously contained only two observations. It now shows every annual observation from 2010 through 2025: 16 values covering a 15-year span. Values are copied from the World Bank API for Albania (ALB), indicator NY.GDP.PCAP.CD, retrieved 24 October 2026; the response identifies its last update as 8 October 2026. The raw response, full-precision CSV and provenance metadata are versioned in assets/albania/data/. Chart and table derive from that same snapshot; table values round to two decimals. No 2026 value is fabricated or interpolated.

The current API value for 2024 is 11374.0085781236, replacing the earlier chart's 11377.78. The 2025 value remains 12998.147869552. The separate total-GDP card was checked against NY.GDP.MKTP.CD: 30540188271.3421, correctly displayed as $30.54bn.

GDP per person is nominal current US dollars, not wages, median income, inflation-adjusted output or purchasing power. The explanatory text identifies exchange-rate and population effects and the possibility of statistical revisions. A zero-baseline chart, individual point inspection, full annual table, CSV and direct primary-source links make the series inspectable.

PISA now includes 2009 in the historical chart and shows the 2025 results separately: mathematics 433, reading 408, science 435, verified against OECD Education GPS. The OECD country note gives the publication date as 8 September 2026 and describes exclusion of schools with fewer than 21 eligible students. OECD does not report a trend for Albania in that cycle. Accordingly, the 2025 values have no connecting line to 2022 and no claimed recovery percentage. The historical series retains the 2022 low-engagement warning. Both the headline card and surrounding education text acknowledge the latest results and comparability limitation.

Primary sources:
- https://api.worldbank.org/v2/country/ALB/indicator/NY.GDP.PCAP.CD?format=json&date=2010:2025&per_page=100
- https://data.worldbank.org/indicator/NY.GDP.PCAP.CD?locations=AL
- https://gpseducation.oecd.org/CountryProfile?plotter=h5&primaryCountry=ALB&topic=PI&treshold=10
- https://www.oecd.org/en/publications/pisa-2025-results-volume-i-country-notes_2d4ff9ea-en/albania_30521d7d-en.html
- https://www.oecd.org/en/publications/pisa-2022-results-volume-i_53f23881-en/full-report/overview-of-performance-trends_ae540546.html

English and Albanian editions share the same numbers. Notes, controls and source labels are translated. Existing population and investigation controls are preserved. This is an application-only release; no database, uploads or mail changes are required.

Validation: all 16 chart values match the raw API response, CSV and provenance file. Both staged pages retain 51 research records and 33 timeline entries, unique IDs and valid fragment targets. Tested English and Albanian chart switching, point click/keyboard inspection, table expansion, 2025 PISA separation, population-chart regression, desktop rendering and a 390px viewport without horizontal overflow. Source subject labels and the population note were also translated. Protected staging remains noindex. Before-images for the five replaced application files are held outside the document root; three new public data files require no database migration.

UI reference: https://www.lazyweb.com/agentic-search/5db9f22f-e1fd-483c-bf1d-a4fddac20874 (time series beside inspectable data; existing editorial design retained).
