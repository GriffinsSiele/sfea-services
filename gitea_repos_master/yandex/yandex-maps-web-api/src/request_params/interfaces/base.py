from requests_logic.base import RequestBaseParamsCFFIAsync


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "authority": "yandex.ru",
        "accept": "*/*",
        "accept-language": "ru-RU,ru;q=0.9",
        "cache-control": "no-cache",
        "device-memory": "8",
        "dnt": "1",
        "downlink": "10",
        "dpr": "1",
        "ect": "4g",
        "pragma": "no-cache",
        "rtt": "50",
        "sec-ch-ua": '"Chromium";v="110", "Not A(Brand";v="24", "Google Chrome";v="110"',
        "sec-ch-ua-arch": '"x86"',
        "sec-ch-ua-bitness": '"64"',
        "sec-ch-ua-full-version": '"110.0.5481.177"',
        "sec-ch-ua-full-version-list": '"Chromium";v="110.0.5481.177", "Not A(Brand";v="24.0.0.0", "Google Chrome";v="110.0.5481.177"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-model": "",
        "sec-ch-ua-platform": '"Linux"',
        "sec-ch-ua-platform-version": '"5.19.0"',
        "sec-ch-ua-wow64": "?0",
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-fetch-site": "same-origin",
        "user-agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36",
        "viewport-width": "1066",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(impersonate="chrome110", *args, **kwargs)
        self.domain = "https://yandex.ru"
        self.method = "GET"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 5
