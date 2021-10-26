from urllib import request as urlrequest

import os
proxy_host = 'cache.llgc.org.uk:80'
os.environ['HTTP_PROXY'] = proxy_host
os.environ['HTTPS_PROXY'] = proxy_host

# host and port of your proxy
url = 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&redirects=1&titles=Charles_Darwin'

req = urlrequest.Request(url)
req.set_proxy(proxy_host, 'https')

response = urlrequest.urlopen(req)
print(response.read().decode('utf8'))