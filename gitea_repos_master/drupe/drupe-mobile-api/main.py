from time import sleep

from settings import PROXY_PASSWORD, PROXY_LOGIN
from src.misc.proxy import ProxyManager
from src.request_params.api.v2.caller_id import CallerId

tokens = ['rNXDv5L6VRRFgSa3tncb6f', '4DsroCT5mHXFJwooY6hfoe']

for _ in range(10):

    for i in range(9):
        proxy = ProxyManager.get_proxy(PROXY_LOGIN, PROXY_PASSWORD)
        c = CallerId(tokens[1], '780055531' + str(i + 10), proxy=proxy)
        r = c.request()
        print(r, r.text)
        sleep(120)

    for k in range(20):
        sleep(60)
        print('Sleep', k + 1, 'min')
