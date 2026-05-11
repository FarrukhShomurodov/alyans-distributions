import json
import re
from pathlib import Path
from urllib import request, error

text = Path('.env').read_text(encoding='utf-8')
mo = re.search(r'^YANDEX_DELIVERY_TOKEN=(.+)$', text, flags=re.MULTILINE)
if not mo:
    raise SystemExit('NO_TOKEN')
token = mo.group(1).strip()
url = 'https://b2b-authproxy.taxi.yandex.net/api/b2b/platform/pickup-points/list'
body = json.dumps({
    'latitude': {'from': 55.74388536242667, 'to': 56.04388536242667},
    'longitude': {'from': 37.32116958203124, 'to': 37.62116958203124},
    'limit': 50,
    'offset': 0,
})
req = request.Request(url, data=body.encode('utf-8'), method='POST', headers={
    'Authorization': 'Bearer ' + token,
    'Accept-Language': 'ru',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
})
try:
    with request.urlopen(req, timeout=15) as r:
        print('STATUS', r.status)
        print('CONTENT_TYPE', r.getheader('Content-Type'))
        data = r.read(5000)
        print(data.decode('utf-8', errors='replace'))
except error.HTTPError as e:
    print('HTTP_ERROR', e.code)
    print(e.read(1000).decode('utf-8', errors='replace'))
except Exception as e:
    print('ERROR', e)
