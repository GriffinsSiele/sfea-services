from requests_logic.base import RequestBaseParamsAsync


class RequestParams(RequestBaseParamsAsync):
    DEFAULT_HEADERS = {
        "Host": "s.callapp.com",
        "Connection": "close",
        "Accept-Encoding": "gzip, deflate",
        "User-Agent": "Mozilla/5.0 (Linux; Android 8.0.0; Samsung Galaxy S7 Build/OPR6.170623.017; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/99.0.4844.58 Mobile Safari/537.36",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://s.callapp.com"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 8
