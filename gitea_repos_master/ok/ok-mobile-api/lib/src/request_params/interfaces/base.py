from requests_logic.base import RequestBaseParamsAsync

from lib.src.config.app import ConfigApp


class RequestParams(RequestBaseParamsAsync):
    DEFAULT_HEADERS = {
        "Accept": "*",
        "accept-language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "User-Agent": f"Mozilla/5.0 (Linux; Android 6.0; Google Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/74.0.3729.186 Mobile Safari/537.36 OkKey/{ConfigApp.APP_KEY} OKAndroid/23.7.10 b23071000 OkApp",
        "Connection": "close",
        "x-requested-with": "ru.ok.android",
        "Accept-Encoding": "gzip, deflate, br",
        "cache-control": "max-age=0",
        "dnt": "1",
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "none",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://api.ok.ru"
        self.method = "POST"
        self.headers = self.DEFAULT_HEADERS
        self.timeout = 3
