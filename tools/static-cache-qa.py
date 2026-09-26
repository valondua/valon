#!/usr/bin/env python3
"""Check the public asset cache boundary using an anonymous HTTP client."""
import json
import sys
from urllib.error import HTTPError
from urllib.request import Request, urlopen

base = sys.argv[1].rstrip("/")
results = []


def request(path, method="GET", headers=None):
    req = Request(base + path, method=method, headers=headers or {})
    try:
        response = urlopen(req, timeout=30)
    except HTTPError as error:
        response = error
    with response:
        response.read()
        return response.status, response.headers


asset = "/wp-content/themes/valon/style.css"
for path, expected in [(asset, "public, max-age=86400"), (asset + "?ver=cache-qa", "public, max-age=2592000")]:
    for method in ["GET", "HEAD"]:
        status, headers = request(path, method)
        assert status == 200, (path, status)
        assert headers.get_all("Cache-Control") == [expected], dict(headers)
        assert "text/css" in headers["Content-Type"], dict(headers)
        assert headers.get("ETag") or headers.get("Last-Modified"), dict(headers)
        validator = {"If-None-Match": headers["ETag"]} if headers.get("ETag") else {"If-Modified-Since": headers["Last-Modified"]}
        status, conditional = request(path, method, validator)
        assert status == 304, (path, status)
        assert conditional.get_all("Cache-Control") == [expected], dict(conditional)
        results.append({"path": path, "method": method, "status": 200, "conditional": status, "cache": expected})

for path in ["/", "/?s=cacheqa", "/?preview=true", "/wp-json/", "/sitemap_index.xml", "/wp-admin/", "/wp-login.php", "/wp-content/themes/valon/nonexistent-cache-qa.css", "/nonexistent-cache-qa.css"]:
    status, headers = request(path)
    policy = headers.get("Cache-Control", "")
    assert "max-age=86400" not in policy and "max-age=2592000" not in policy, (path, status, policy)
    assert status < 500, (path, status)
    if "nonexistent" in path:
        assert status == 404, (path, status)
    results.append({"path": path, "status": status, "cache": policy})

status, headers = request(asset, "POST")
assert "max-age=86400" not in headers.get("Cache-Control", ""), dict(headers)
assert "max-age=2592000" not in headers.get("Cache-Control", ""), dict(headers)
results.append({"path": asset, "method": "POST", "status": status, "cache": headers.get("Cache-Control", "")})
print(json.dumps(results, indent=2))
