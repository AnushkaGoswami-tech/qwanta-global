import json, pathlib, urllib.request, time
from concurrent.futures import ThreadPoolExecutor
root=pathlib.Path(__file__).resolve().parent
photos=json.loads((root/'photos.json').read_text())
def get(p):
 target=root/'public'/p['local'];target.parent.mkdir(exist_ok=True)
 req=urllib.request.Request(p['url'],headers={'User-Agent':'QwantaGlobalSourcing/1.0 (mraj7848@gmail.com)'})
 with urllib.request.urlopen(req,timeout=90) as r:
  content=r.read()
  if not content.startswith(b'\xff\xd8'):raise ValueError('Not JPEG '+p['file'])
 target.write_bytes(content); print('Saved',target.name)
for p in photos:
 try:get(p)
 except Exception as e:print('FAILED',p['file'],e)
 time.sleep(1)
