from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "authority": "m.avito.ru",
        "scheme": "https",
        "accept": "application/json, text/plain, */*",
        "accept-encoding": "gzip, deflate, br",
        "content-type": "application/x-www-form-urlencoded",
        "dnt": "1",
        "origin": "https://m.avito.ru",
        "sec-fetch-site": "same-origin",
        "sec-fetch-mode": "cors",
        "sec-fetch-dest": "empty",
        "referer": "https://m.avito.ru",
        "accept-language": "ru-RU,ru;q=0.9",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome110", *args, **kwargs)
        self.domain = "https://m.avito.ru"
        self.method = "POST"
        self.headers = self.DEFAULT_HEADERS
        self.verify = False
        self.timeout = 8
