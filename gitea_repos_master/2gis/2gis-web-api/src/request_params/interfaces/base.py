from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "authority": "catalog.api.2gis.ru",
        "accept": "application/json, text/plain, */*",
        "accept-language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "dnt": "1",
        "origin": "https://2gis.ru",
        "referer": "https://2gis.ru/",
        "sec-ch-ua": '"Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-fetch-site": "same-site",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome110", *args, **kwargs)
        self.domain = "https://catalog.api.2gis.ru"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 5
        self.verify = False
