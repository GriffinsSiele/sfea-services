import base64

import requests
from pydash import get


class ProxyManager:
    url = 'https://i-sphere.ru/2.00/get_proxies.php?country=ru&status=1&limit=1&order=lasttime'
    headers = {
        'Authorization': f'Basic TOKEN'  # Base64 encode login, password
    }

    @staticmethod
    def _get_proxy(login, password):
        token = base64.b64encode(f'{login}:{password}'.encode()).decode()
        ProxyManager.headers['Authorization'] = f'Basic {token}'
        return requests.get(ProxyManager.url, headers=ProxyManager.headers)

    @staticmethod
    def get_proxy(login, password):
        response = ProxyManager._get_proxy(login, password)
        return ProxyManager.proxy_adapter(response.json())

    @staticmethod
    def proxy_adapter(response):
        server = get(response, '0.server')
        port = get(response, '0.port')
        login = get(response, '0.login')
        password = get(response, '0.password')

        url = f'{login}:{password}@{server}:{port}'

        return {
            'http': 'http://' + url,
        }
