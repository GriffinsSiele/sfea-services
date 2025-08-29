from requests_logic.base import RequestBaseParamsH2


class RequestParams(RequestBaseParamsH2):
    DEFAULT_HEADERS = {
        "ClientAppKey": "ANDROID_TESTS",
        "Content-Type": "application/json; charset=utf-8",
        "Connection": "close",
        "Accept-Encoding": "gzip, deflate",
        "User-Agent": "okhttp/4.6.0",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://mobileapps.prod.getsimpler.me:443"
        self.method = "POST"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 8
