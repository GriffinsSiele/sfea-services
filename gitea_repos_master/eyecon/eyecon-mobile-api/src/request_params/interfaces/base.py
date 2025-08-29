from requests_logic.base import RequestBaseParamsAsync

from src.config.app import ConfigApp


class RequestParams(RequestBaseParamsAsync):
    DEFAULT_HEADERS = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36",
        "accept": "application/json",
        "e-auth-v": ConfigApp.APP_AUTH_V,
        "e-auth": "4407cee6-3bd1-4309-b693-d752d4059dae",
        "e-auth-c": "35",
        "e-auth-k": ConfigApp.APP_HASH,
        "accept-charset": "UTF-8",
        "Host": "api.eyecon-app.com",
        "Connection": "Keep-Alive",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://api.eyecon-app.com"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.verify = False
        self.timeout = 8
