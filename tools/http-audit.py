#!/usr/bin/env python3
"""Read-only HTTP acceptance checks against the local preview."""
import xml.etree.ElementTree as ET
import os, json, urllib.request, urllib.error, concurrent.futures
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
BASE=os.environ.get('VALON_AUDIT_BASE','http://localhost:8094');INDEXABLE=os.environ.get('VALON_AUDIT_INDEXABLE')=='1'
class Page(HTMLParser):
 def __init__(self): super().__init__(); self.h1=0; self.meta={}; self.canonical='';self.alternates={};self.ids=[];self.schema=[];self.in_schema=False;self.buf='';self.iframes=0;self.links=[]
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='h1':self.h1+=1
  if tag=='meta':self.meta[a.get('name','')]=a.get('content','')
  if tag=='link' and a.get('rel')=='canonical':self.canonical=a.get('href','')
  if tag=='link' and a.get('hreflang'):self.alternates[a['hreflang']]=a.get('href')
  if tag=='a' and a.get('href'):self.links.append(a['href'])
  if 'id' in a:self.ids.append(a['id'])
  if tag=='iframe':self.iframes+=1
  if tag=='script' and a.get('type')=='application/ld+json':self.in_schema=True;self.buf=''
 def handle_data(self,d):
  if self.in_schema:self.buf+=d
 def handle_endtag(self,tag):
  if tag=='script' and self.in_schema:
   self.in_schema=False
   try:self.schema.append(json.loads(self.buf))
   except json.JSONDecodeError:pass
def get(path):
 try:
  with urllib.request.urlopen(BASE+path,timeout=20) as r: body=r.read().decode();status=r.status;url=r.url
 except urllib.error.HTTPError as e:body=e.read().decode();status=e.code;url=e.url
 p=Page();p.feed(body);return status,url,p,body
results=[];failures=[]
def check(ok,name):
 results.append({'check':name,'passed':bool(ok)})
 if not ok:failures.append(name)
manifest=json.loads(Path('review/content/pages.json').read_text())
paths=['/','/sq/']+[('/' if lang=='en' else '/sq/')+p['slug']+'/' for route,langs in manifest.items() if route!='home' for lang,p in langs.items()]
for path in paths:
 status,url,p,body=get(path);check(status==200,'Core route '+path);check(p.h1==1,'Single H1 '+path);check(len(p.ids)==len(set(p.ids)),'Unique DOM IDs '+path)
 if path in ['/','/sq/','/about/','/sq/rreth-meje/']:
  check(p.canonical==BASE+path if INDEXABLE else (p.canonical==BASE+path or (not p.canonical and 'noindex' in p.meta.get('robots',''))),'Canonical output '+path)
  check(len(p.alternates)>=2 and all(u.startswith(BASE+'/') for u in p.alternates.values()),'Real language alternates '+path)
  check(('noindex' not in p.meta.get('robots','')) if INDEXABLE else ('noindex' in p.meta.get('robots','')),'Expected indexability '+path)
  check(bool(p.meta.get('description')),'Description '+path)
  graph=[n for data in p.schema for n in data.get('@graph',[])];people=[n for n in graph if n.get('@type')=='Person']
  check(len(people)==1 and people[0].get('name')=='Valon Asani','One consistent Person identity '+path)
  if 'about' in path or 'rreth-meje' in path:check(any('ProfilePage' in n.get('@type',[]) for n in graph),'ProfilePage '+path)
 if path in ['/','/sq/']:check(p.iframes==0,'Players deferred '+path)
source=json.loads(Path('.local/source/posts.json').read_text())+json.loads(Path('.local/source/pages.json').read_text())
legacy_paths=list(dict.fromkeys(urlparse(p['link']).path for p in source))
def verify(path):
 status,url,_,_=get(path);return path,status,url
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
 for path,status,url in pool.map(verify,legacy_paths):check(status==200 and url==BASE+path,'Preserved legacy URL '+path)
status,url,p,body=get('/?page_id=1233');check(status==200 and url==BASE+'/now/','Broken legacy Now redirects to /now/')
status,url,p,body=get('/not-a-real-valon-page/');check(status==404 and p.h1==1,'Useful 404 retains 404 HTTP status')
for path in ['/writing/page/2/','/category/relationships/','/category/personal-growth/','/category/business-technology/','/category/life-between-cultures/','/sq/category/relationships-sq/']:
 check(get(path)[0]==200,'Archive route '+path)

for path in ['/wp-content/themes/valon/review/content/translations.json','/wp-content/themes/valon/.env.example','/wp-content/themes/valon/tools/qa.php','/wp-content/plugins/valon-platform/content/translations.json']:
 check(get(path)[0] in [403,404],'Private/dev payload unavailable '+path)
if INDEXABLE:
 status,_,_,xml=get('/sitemap_index.xml');check(status==200,'Indexable sitemap available')
 status,_,_,xml=get('/post-sitemap.xml');check(status==200,'Article sitemap available')
 if status==200:
  locs=[n.text for n in ET.fromstring(xml).findall('{*}url/{*}loc')]
  check(len(locs)==78 and all(u.startswith(BASE+'/') for u in locs),'Sitemap includes 78 original articles on the current host')
  draft_slugs=[p['slug'] for p in json.loads(Path('review/content/translations.json').read_text())]
  check(not any(s in u for s in draft_slugs for u in locs),'Unapproved translations absent from sitemap')

report={'base':BASE,'assertions':len(results),'passed':sum(r['passed'] for r in results),'failures':failures,'checks':results}
Path('docs/indexable-validation.json' if INDEXABLE else 'docs/http-validation.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps({k:v for k,v in report.items() if k!='checks'},indent=2))
raise SystemExit(bool(failures))
