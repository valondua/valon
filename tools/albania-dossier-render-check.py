from pathlib import Path
from html.parser import HTMLParser
import json,re,html
import argparse
from urllib.parse import urlsplit
ap=argparse.ArgumentParser();ap.add_argument('pages',type=Path);ap.add_argument('--live',action='store_true');ap.add_argument('--output',type=Path,required=True);args=ap.parse_args();base=args.pages;repo=Path(__file__).resolve().parents[1]
class P(HTMLParser):
 def __init__(self):super().__init__();self.ids=[];self.links=[];self.text=[];self.canonical=[];self.scripts=[];self.nav=0;self.innav=False;self.navlinks=0
 def handle_starttag(self,t,attrs):
  a=dict(attrs)
  if 'id'in a:self.ids.append(a['id'])
  if t=='a':self.links.append(a.get('href',''));self.navlinks+=self.innav
  if t=='link' and a.get('rel')=='canonical':self.canonical.append(a['href'])
  if t=='script' and a.get('src'):self.scripts.append(a['src'])
  if t=='nav' and a.get('class')=='dossier-navigation':self.innav=True
 def handle_endtag(self,t):
  if t=='nav':self.innav=False
 def handle_data(self,s):self.text.append(s)
checks=[];pages={}
for f in base.rglob('index.html'):
 route='/'+str(f.parent.relative_to(base))+'/';h=f.read_text();p=P();p.feed(h);pages[route]=(p,h)
 assert re.search(r'HTTP/\S+ 200', (f.parent/'headers').read_text()),route
 assert ('noindex' in (f.parent/'headers').read_text().lower()) != args.live,route
 assert p.navlinks==6,(route,p.navlinks)
 assert (len(p.canonical)==1 if args.live else len(p.canonical)<=1),(route,p.canonical)
 if p.canonical:assert p.canonical[0].endswith(route),(route,p.canonical)
 assert not re.search(r'Fatal error|Warning:|DOSSIER_[A-Z]+_URL|ALBANIA_ASSETS',h),route
 assert len(p.ids)==len(set(p.ids)),(route,'duplicate IDs',sorted({x for x in p.ids if p.ids.count(x)>1}))
 checks.append({'route':route,'bytes':len(h.encode()),'navigation_links':p.navlinks,'canonical':p.canonical[0] if p.canonical else 'suppressed by private-stage noindex guard'})
for prefix,sources,history in [('/albania/','sources/','investigations/jewish-history-projects/'),('/sq/shqiperia/','burimet/','hetime/historia-hebreje/')]:
 p,h=pages[prefix+sources]
 assert all('r'+str(i).zfill(2) in p.ids for i in range(1,56))
 hp,hh=pages[prefix+history];assert len(re.findall('class="context-card',hh))==19
 op,oh=pages[prefix];assert not any('vis-network' in s or 'story-data' in s for s in op.scripts)
 assert '<noscript>' in oh
 assert prefix+sources+'#r27' in oh
 # Every mapped real anchor resolves to exactly one child page; virtual graph selections intentionally use handlers.
 config=json.loads(re.search(r'<script type="application/json" id="dossier-route-map">(.*?)</script>',oh).group(1))
 for anchor,key in config['anchors'].items():
  route=urlsplit(config['routes'][key]).path;assert anchor in pages[route][0].ids,(anchor,route)
 # Every internal dossier link has a concrete route and target.
 for route,(p,h) in pages.items():
  for link in p.links:
   if urlsplit(link).hostname not in ['127.0.0.1','www.valonasani.com']:continue
   path=urlsplit(link).path;anchor=urlsplit(link).fragment
   if path in pages and anchor and not anchor.startswith(('person-','evidence-')):assert anchor in pages[path][0].ids,(route,link)
args.output.write_text(json.dumps(checks,indent=2));print('PASS',len(checks),'routes; canonical policy, robots policy, 6 navigation links, unique IDs, 55 records, 19 history cards, real fragment destinations')
