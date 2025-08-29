import requests

from src.request_params.interfaces.tls_adapter import TLSAdapter


class RequestParams(object):
    METHOD = 'POST'

    def __init__(self, proxy=None):
        self.proxies = proxy

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_payload(
        ), self.get_query(), self.METHOD

    def get_headers(self):
        user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.41 Safari/537.36'

        return {
            'Host': self.get_base_url().replace('https://', ''),
            'authority': self.get_base_url().replace('https://', ''),
            'accept': '*/*',
            'accept-language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'dnt': '1',
            'origin': 'https://web.icq.com',
            'referer': 'https://web.icq.com/',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Linux"',
            'sec-fetch-dest': 'empty',
            'sec-fetch-mode': 'cors',
            'sec-fetch-site': 'cross-site',
            'user-agent': user_agent
        }

    def get_base_url(self):
        return 'https://u.icq.net'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return None

    def get_query(self):
        return None

    def request(self):
        url, headers, data, query, method = self.generate()

        session = requests.session()
        session.mount('https://', TLSAdapter())

        response = session.request(method,
                                   url,
                                   json=data,
                                   params=query,
                                   headers=headers,
                                   proxies=self.proxies,
                                   verify=False)
        return response
