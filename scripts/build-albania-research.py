#!/usr/bin/env python3
"""Render the reviewed research JSON to static HTML; no third-party dependencies."""
import html, json
from pathlib import Path
repo = Path(__file__).resolve().parents[1]
records = json.loads((repo / 'assets/albania/research-register.json').read_text())['records']
e=html.escape
out=['<section class="section research-expansion" id="research-update" aria-labelledby="research-title"><div class="kicker">Research update · 23 October 2026</div><h2 id="research-title">Follow the records. Test the connections.</h2><p class="deck">Land, ownership, public money, diplomacy and the claims behind the debate.</p><p>This update brings together the new research on public decisions, companies, money and community claims. Each entry separates what its sources support from what remains unresolved. Older case reports are dated evidence, not a claim that the latest court outcome has been verified.</p><div class="notice"><strong>Corrections made before publication:</strong> Albania’s UN vote; the World Bank brief’s version and poverty estimates; the AIDA register’s purpose; the OSF grant claims; and the distinction between 5D contracts and payments. Read the correction entries below.</div><form id="research-filters" role="search" hidden><div class="research-controls"><div class="research-field"><label for="research-search">Search this research</label><input id="research-search" type="search" placeholder="Try Sazan, grants, airport or a name…" autocomplete="off"></div><div class="research-field"><label for="research-topic">Topic</label><select id="research-topic"><option value="">All topics</option>']
for topic in dict.fromkeys(r['topic'] for r in records): out.append(f'<option>{e(topic)}</option>')
out+=['</select></div><div class="research-field"><label for="research-status">Evidence status</label><select id="research-status"><option value="">All evidence statuses</option>']
for status in dict.fromkeys(r['status'] for r in records):out.append(f'<option>{e(status)}</option>')
out+=['</select></div><button type="reset">Clear filters</button></div><p id="research-count" role="status" aria-live="polite"></p></form><p class="research-legend">“Documented record” means the described document supports that limited claim. “Official account” attributes an institution’s statement. “Reported relationship” and “Reported findings” rely on reporting or summaries. Allegations, disputed claims and open questions are not findings of guilt.</p><div id="research-records">']
for r in records:
 out.append(f'<article class="research-record" id="{r["id"].lower()}" data-topic="{e(r["topic"])}" data-status="{e(r["status"])}"><p class="research-label">{r["id"]} · {e(r["topic"])} · <strong>{e(r["status"])}</strong></p><h3>{e(r["title"])}</h3><p>{e(r["body"])}</p><details><summary>Evidence, limits and sources</summary><p><strong>Limit:</strong> {e(r["limit"])}</p><ul>')
 for j,s in enumerate(r['sources']):
  from urllib.parse import urlparse
  label=s['label'] if not s['label'].startswith('Source ') else urlparse(s['url']).hostname
  out.append(f'<li><a href="{e(s["url"],quote=True)}" target="_blank" rel="noopener noreferrer">{e(label)} ↗</a></li>')
 out.append(f'</ul><a class="research-permalink" href="#{r["id"].lower()}">Link to this entry</a></details></article>')
out+=['</div><p id="research-empty" hidden>No entries match. Clear a filter or try another name.</p><p class="research-method">Reviewed 23 October 2026. Retrieval failures and incomplete original records remain visible as limitations. Source availability does not by itself verify a claim. <a href="#sources">Original investigation source library ↓</a></p></section>']
repo.joinpath('template-parts/albania-research-register.html').write_text('\n'.join(out)+'\n')
print(len(records),'entries')
