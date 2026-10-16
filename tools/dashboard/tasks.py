#!/usr/bin/env python3
"""Merge reviewed task proposals into a private local register; never executes work."""
import argparse,datetime,fcntl,hashlib,json,os,pathlib,tempfile,urllib.parse

def key(task):
    url=urllib.parse.urlsplit(task['url'])
    if url.scheme!='https' or url.hostname not in ('www.valonasani.com','valonasani.com') or url.username or url.password:
        raise ValueError('Expected a canonical valonasani.com HTTPS URL')
    canonical='https://www.valonasani.com'+(url.path.rstrip('/') or '')+'/'
    problem=task['problem_type'].strip().lower()
    if not problem: raise ValueError('problem_type is required')
    return canonical,problem,'SEO-'+hashlib.sha256((canonical+'|'+problem).encode()).hexdigest()[:12].upper()

def merge(existing, incoming):
    records={t['id']:t for t in existing}
    for proposed in incoming:
        canonical,problem,identifier=key(proposed)
        for field in ['title','evidence','proposed_change','success_criterion','evaluation_period','verified']:
            if not proposed.get(field): raise ValueError('Missing '+field)
        old=records.get(identifier,{})
        record={**old,**proposed,'id':identifier,'url':canonical,'problem_type':problem}
        record['status']=old.get('status','proposed') # A re-observation does not reopen completed work.
        record['created_at']=old.get('created_at',proposed['verified'])
        records[identifier]=record
    return sorted(records.values(),key=lambda t:t['id'])

if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--register',required=True); parser.add_argument('--input',required=True)
    args=parser.parse_args(); path=pathlib.Path(args.register).resolve()
    repo=pathlib.Path(__file__).resolve().parents[2]
    if path.is_relative_to(repo): raise SystemExit('Keep the private register outside the repository/web root.')
    path.parent.mkdir(parents=True,exist_ok=True,mode=0o700)
    lock=os.open(str(path)+'.lock',os.O_CREAT|os.O_RDWR,0o600)
    with os.fdopen(lock,'w') as handle:
        fcntl.flock(handle,fcntl.LOCK_EX)
        existing=json.loads(path.read_text()) if path.exists() else {'tasks':[]}
        incoming=json.loads(pathlib.Path(args.input).read_text())
        tasks=merge(existing['tasks'],incoming['tasks'])
        fd,tmp=tempfile.mkstemp(dir=path.parent,prefix='.tasks-')
        with os.fdopen(fd,'w') as output: json.dump({'schema_version':1,'tasks':tasks},output,ensure_ascii=False,indent=2)
        os.replace(tmp,path)
    print(f'Saved {len(tasks)} proposals; no project actions executed.')
