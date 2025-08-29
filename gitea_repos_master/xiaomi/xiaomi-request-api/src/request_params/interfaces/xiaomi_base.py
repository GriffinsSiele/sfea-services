from src.request_params.interfaces.base import BaseRequestParams


class XiaomiBaseParams(BaseRequestParams):
    """Интерфейс, определяет дополнительные настройки для работы с сайтом xiaomi"""

    DEFAULT_HEADERS = {
        "Connection": "keep-alive",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "origin": "https://account.xiaomi.com",
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": '"Linux"',
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = self.DEFAULT_HEADERS
