import requests
from request_logic.tls_adapter import TLSAdapter


class RequestParams(object):
    METHOD = 'POST'

    def __init__(self, proxy=None):
        self.proxies = proxy
        self.session = requests.session()

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_payload(), self.METHOD

    def get_headers(self):
        user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36'
        return {
            'Host': self.get_base_url().replace('https://', ''),
            'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control': 'max-age=0',
            'Connection': 'keep-alive',
            'DNT': '1',
            'User-Agent': user_agent,
            'sec-ch-ua': '"Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Linux"'
        }

    def get_base_url(self):
        return 'https://dkbm-web.autoins.ru'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return None

    def get_session(self):
        return self.session

    def set_session(self, session):
        self.session = session

    def request(self):
        url, headers, data, method = self.generate()

        self.session.mount('https://', TLSAdapter())

        return self.session.request(method, url, data=data, headers=headers, proxies=self.proxies, verify=False)
