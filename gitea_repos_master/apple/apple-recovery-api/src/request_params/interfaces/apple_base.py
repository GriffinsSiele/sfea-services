from src.request_params.interfaces.base import BaseRequestParams


class AppleBaseParams(BaseRequestParams):
    """Интерфейс, определяет дополнительные настройки для работы с сайтом apple"""

    DEFAULT_HEADERS = {
        "Accept-Language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
        "Connection": "keep-alive",
        "Sec-Fetch-Site": "same-origin",
        "sec-ch-ua": '"Not=A?Brand";v="99", "Chromium";v="118"',
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"macOS"',
        "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Iron Safari/537.36",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = self.DEFAULT_HEADERS
