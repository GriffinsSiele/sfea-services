from src.request_params.interfaces.base import BaseRequestParams


class CaptchaServiceBaseRequestParams(BaseRequestParams):
    """Дополнительные настройки для работы с сервисом капч"""

    DEFAULT_HEADERS = {
        "accept": "application/json",
    }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = self.DEFAULT_HEADERS
