#!/usr/bin/env python3
"""Private loopback article dictation workspace. Run with python3 server.py."""
import json, os, time
from pathlib import Path
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlsplit
ROOT=Path(__file__).resolve().parents[2]
STATIC=Path(__file__).resolve().parent
DATA=ROOT/'.local/article-voice'
DATA.mkdir(exist_ok=True,parents=True)
ARTICLES=json.loads((DATA/'articles.json').read_text())
IDS={x['id'] for x in ARTICLES['articles']}
PORT=int(os.environ.get('ARTICLE_VOICE_PORT','8768'))
class Handler(BaseHTTPRequestHandler):
 def reply(self,code,data,kind='application/json'):
  body=json.dumps(data,ensure_ascii=False).encode() if kind=='application/json' else data
  self.send_response(code);self.send_header('Content-Type',kind+'; charset=utf-8');self.send_header('Content-Length',str(len(body)));self.send_header('Cache-Control','no-store');self.send_header('X-Content-Type-Options','nosniff');self.send_header('X-Frame-Options','DENY');self.end_headers();self.wfile.write(body)
 def allowed(self):
  return self.headers.get('Host') in {f'localhost:{PORT}',f'127.0.0.1:{PORT}'}
 def do_GET(self):
  if not self.allowed():return self.reply(403,{'error':'Invalid host'})
  path=urlsplit(self.path).path
  if path=='/api/articles':return self.reply(200,ARTICLES)
  if path=='/api/notes':return self.reply(200,{p.stem:json.loads(p.read_text()) for p in DATA.glob('[0-9]*.json')})
  names={'/':'index.html','/app.js':'app.js','/style.css':'style.css'}
  if path not in names:return self.reply(404,{'error':'Not found'})
  name=names[path];return self.reply(200,(STATIC/name).read_bytes(),{'html':'text/html','js':'text/javascript','css':'text/css'}[name.split('.')[-1]])
 def do_POST(self):
  if not self.allowed() or self.headers.get('Origin') not in {f'http://localhost:{PORT}',f'http://127.0.0.1:{PORT}'}:return self.reply(403,{'error':'Same-origin requests only'})
  if self.path!='/api/notes' or self.headers.get('Content-Type')!='application/json':return self.reply(400,{'error':'Invalid request'})
  try:
   length=int(self.headers.get('Content-Length','0'))
   if not 0<length<200000:raise ValueError()
   d=json.loads(self.rfile.read(length)); ident=d['id']
   if ident not in IDS or not isinstance(d['text'],str) or d.get('status') not in ['draft','ready']:raise ValueError()
   path=DATA/(ident+'.json');old=json.loads(path.read_text()) if path.exists() else {'revision':0}
   if d.get('revision')!=old['revision']:return self.reply(409,{'error':'Notes changed in another tab. Copy your notes before reloading.'})
   note={'id':ident,'text':d['text'],'status':d['status'],'revision':old['revision']+1,'saved_at':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime())}
   temp=path.with_suffix('.tmp');temp.write_text(json.dumps(note,ensure_ascii=False,indent=2));temp.chmod(0o600);temp.replace(path);return self.reply(200,note)
  except (ValueError,KeyError,TypeError):return self.reply(400,{'error':'Invalid note'})
  except OSError:return self.reply(500,{'error':'Could not save to disk; keep this tab open and export your notes.'})
if __name__=='__main__':
 print(f'Article voice workspace: http://localhost:{PORT}',flush=True)
 HTTPServer(('127.0.0.1',PORT),Handler).serve_forever()
