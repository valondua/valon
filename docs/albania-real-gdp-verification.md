# GDP comparison — 24 October 2026

The nominal series remains NY.GDP.PCAP.CD (current US dollars). The added series is World Bank NY.GDP.PCAP.KD (constant 2015 US dollars), Albania, annual 2010–2025. The API returned 16 non-null observations for each series, dataset last updated 8 October 2026. The nominal response was compared with the existing archive and is unchanged.

Sources:
- https://api.worldbank.org/v2/country/ALB/indicator/NY.GDP.PCAP.KD?date=2010:2025&format=json&per_page=100
- https://data.worldbank.org/indicator/NY.GDP.PCAP.KD?locations=AL
- https://databank.worldbank.org/metadataglossary/world-development-indicators/series/NY.GDP.PCAP.KD
- https://datahelpdesk.worldbank.org/knowledgebase/articles/114943-how-do-you-derive-constant-price-series-in-usd

The 2025 real value is 6877.07720478965, compared with nominal 12998.147869552. In the 2015 reference year both equal 4199.53912925398. No interpolation, projections or manual CPI subtraction are used. The archived JSON and downloadable CSV retain source precision; chart readouts round to two decimals.

Constant-dollar data preserve constant-local-currency growth using the reference-year dollar value. The gap between the two dollar series therefore is not an inflation estimate. Both depend on population and are distinct from wages, household incomes and PPP measures. The English and Albanian explanation states these limits. Charts share dates and a zero-to-14000 vertical scale, with separate unit headings, and stack on mobile.

Validation: `node tools/albania-gdp.test.cjs` checks series identity, complete years, precise CSV values, reference-year equality, shared scale and translation terminology. Existing bilingual accuracy tests also run. Stage and live browser verification supplement the data tests.
