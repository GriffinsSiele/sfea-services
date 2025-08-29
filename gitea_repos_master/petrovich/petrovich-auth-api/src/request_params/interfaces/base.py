from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "Accept": "application/json, text/plain, */*",
        "Accept-Language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "Connection": "keep-alive",
        "Content-Type": "application/json",
        "DNT": "1",
        "Origin": "https://petrovich.ru",
        "Referer": "https://petrovich.ru/cart/pre-order/fiz/",
        "Sec-Fetch-Dest": "empty",
        "Sec-Fetch-Mode": "cors",
        "Sec-Fetch-Site": "same-site",
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
        "sec-ch-ua": '"Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
        "x-requested-with": "XmlHttpRequest",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome124", *args, **kwargs)
        self.domain = "https://api.petrovich.ru"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.verify = False
        self.timeout = 8
