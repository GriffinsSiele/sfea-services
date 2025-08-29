from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "Accept": "application/json",
        "Accept-Language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "Connection": "keep-alive",
        "DNT": "1",
        "Origin": "https://ipoteka.domclick.ru",
        "Referer": "https://ipoteka.domclick.ru/",
        "Sec-Fetch-Dest": "empty",
        "Sec-Fetch-Mode": "cors",
        "Sec-Fetch-Site": "same-site",
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "sec-ch-ua": '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
        "x-User-Context": "CUSTOMER",
        "x-User-Role": "CUSTOMER",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://api.domclick.ru"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.verify = False
        self.timeout = 5
