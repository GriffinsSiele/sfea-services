from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "accept-encoding": "gzip",
        "connection": "Keep-Alive",
        "user-agent": "okhttp/4.9.1",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome99_android", *args, **kwargs)
        self.timeout = 5
        self.headers = self.DEFAULT_HEADERS
        self.query = self.query if self.query else {}
