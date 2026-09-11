#!/usr/bin/env python3
"""Package deployable code only; private drafts, analytics, credentials and dev tools are excluded."""
from pathlib import Path
import zipfile,hashlib,json
root=Path(__file__).resolve().parents[1];out=root/'dist';out.mkdir(exist_ok=True)
theme_files=[p for p in root.glob('*.php')]+[root/'style.css',root/'theme.json']
for folder in ['assets','js','template-parts']:theme_files+=list((root/folder).rglob('*'))
plugin_files=list((root/'plugins/valon-platform').rglob('*'))
def bundle(name,prefix,files,relative):
 with zipfile.ZipFile(out/name,'w',zipfile.ZIP_DEFLATED) as z:
  for p in sorted(set(files)):
   if p.is_file():z.write(p, prefix+'/'+str(p.relative_to(relative)))
 return {'file':name,'sha256':hashlib.sha256((out/name).read_bytes()).hexdigest(),'bytes':(out/name).stat().st_size}
manifest=[bundle('valon-theme.zip','valon',theme_files,root),bundle('valon-platform.zip','valon-platform',plugin_files,root/'plugins/valon-platform')]
for row in manifest:
 with zipfile.ZipFile(out/row['file']) as z:
  assert not any(any(part in name for part in ['.env','review/','translations.json','pages.json','backup','.git/']) for name in z.namelist())
(out/'SHA256.json').write_text(json.dumps(manifest,indent=2)+'\n')
print(json.dumps(manifest,indent=2))
