import requests

from src.config.app import X_CALLERIDSDK_ANDROID_NUMBER, APPLICATION_VERSION_CODE, APPLICATION_VERSION, USER_AGENT
from src.request_params.interfaces.tls_adapter import TLSAdapter


class RequestParams(object):
    METHOD = 'GET'

    def __init__(self, proxy=None):
        self.proxies = proxy

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_payload(), self.METHOD

    def get_headers(self):
        return {
            'Host': self.get_base_url().replace('https://', ''),
            'X-CallerIdSdk-gzip': 'true',
            'Connection': 'close',
            'Accept-Encoding': 'gzip, deflate',
            'X-CallerIdSdk-Android-Number': X_CALLERIDSDK_ANDROID_NUMBER,
            'APPLICATION-VERSION': APPLICATION_VERSION,
            'User-Agent': USER_AGENT,
            'APPLICATION-VERSION-CODE': APPLICATION_VERSION_CODE,
        }

    def get_base_url(self):
        return 'https://api.acallerid.com'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return None

    def request(self):
        url, headers, data, method = self.generate()

        session = requests.session()
        session.mount('https://', TLSAdapter())

        return session.request(method, url, data=data, headers=headers, proxies=self.proxies)
