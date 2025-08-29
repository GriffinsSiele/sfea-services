from src.config import ConfigApp
from src.request_params.interfaces.apple_base_page import AppleStartPageRequestParams


class AppleMainGet(AppleStartPageRequestParams):
    """Обертка над запросом для получения главной страницы сайта apple"""

    def __init__(
        self,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.BASE_URL,
            *args,
            **kwargs,
        )
        self.headers = {
            **self.headers,
            "Cache-Control": "no-cache",
            "Pragma": "no-cache",
        }
