from src.config import settings
from src.request_params.interfaces.base import BaseAsyncRequestClient


class DomruRequestClient(BaseAsyncRequestClient):

    HEADERS = {
        "Accept": "application/json, text/plain, */*",
        "Accept-Encoding": "gzip, deflate, br, zstd",
        "Accept-Language": "en-US,en;q=0.9,ru-RU;q=0.8,ru;q=0.7",
        "Connection": "keep-alive",
        "Host": "api-profile.web-api.dom.ru",
        "Sec-CH-UA": '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
        "Sec-CH-UA-Mobile": "?0",
        "Sec-CH-UA-Platform": '"Windows"',
        "Sec-Fetch-Dest": "empty",
        "Sec-Fetch-Mode": "cors",
        "Sec-Fetch-Site": "same-site",
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "X-Requested-With": "XMLHttpRequest",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(
            url=settings.DOMRU_SEARCH_URL,
            headers={**self.HEADERS, "ProviderId": kwargs.pop("provider_id", "")},
            *args,
            **kwargs,
        )
