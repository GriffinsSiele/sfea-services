from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "Accept-Encoding": "gzip",
        "Host": "api.papajohns.ru",
        "Content-Type": "application/json",
        "accept": "application/json",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome99_android", *args, **kwargs)
        self.domain = "https://api.papajohns.ru"
        self.method = "POST"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 10
