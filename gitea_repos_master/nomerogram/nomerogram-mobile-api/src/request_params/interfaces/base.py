import requests
from request_logic.tls_adapter import TLSAdapter
from requests import Session

from src.config.app import APP_VERSION


class RequestParams(object):
    METHOD = 'GET'

    def __init__(self, proxy=None):
        self.proxies = proxy
        self.session = Session()

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_payload(), self.get_query(), self.get_cookies(
        ), self.METHOD

    def get_headers(self):
        return {
            "User-Agent": f"Nomerogram/{APP_VERSION} (Android; unknown; Google; 1.50)",
            "APP-BUILD-VERSION": APP_VERSION,
            "OS": "android",
            "Connection": "close",
            "Accept-Encoding": "gzip, deflate"
        }

    def get_base_url(self):
        return 'https://www.nomerogram.ru:443'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return None

    def get_query(self):
        return {}

    def get_cookies(self):
        return {}

    def get_session(self):
        return self.session

    def request(self):
        url, headers, data, query, cookies, method = self.generate()

        self.session.mount('https://', TLSAdapter())
        self.session.verify = False

        return self.session.request(method,
                                    url,
                                    json=data,
                                    headers=headers,
                                    proxies=self.proxies,
                                    params=query,
                                    cookies=cookies)
