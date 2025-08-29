from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "User-Agent": "8.6.2 (Xiaomi Mi A2; Android 11/30; okhttp/4.10.0)",
        "Connection": "Keep-Alive",
        "Content-Type": "application/x-www-form-urlencoded",
        "MobileApiFeatures": "24cfH",
        "MobileApiVersion": "8.6",
        "MobileApiClient": "GOOGLE",
        "Transfer-Encoding": "chunked",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome124", *args, **kwargs)
        self.domain = "https://mobileapp.russianpost.ru"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.verify = False
        self.timeout = 5
