import requests

from src.config.app import ConfigApp
from src.request_params.interfaces.tls_adapter import TLSAdapter


class RequestParams(object):
    METHOD = 'POST'

    def __init__(self, proxy=None):
        self.proxies = proxy

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_query_params(), self.get_payload(), self.METHOD

    def get_headers(self):
        return {
            "X-App-Version": ConfigApp.APP_VERSION,
            "Content-Type": "application/json; charset=utf-8",
            "Connection": "close",
            "Accept-Encoding": "gzip, deflate",
        }

    def get_base_url(self):
        return 'https://pbssrv-centralevents.com'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return None

    def _get_payload(self):
        return {}

    def get_query_params(self):
        return {}

    def request(self):
        url, headers, query, data, method = self.generate()

        session = requests.session()
        session.mount('https://', TLSAdapter())

        return session.request(method, url, data=data, headers=headers, params=query, proxies=self.proxies)
