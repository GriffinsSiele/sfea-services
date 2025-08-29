from src.config import ConfigApp
from src.request_params.interfaces.apple_base_requests import AppleBaseRequestParams


class AppleResultGet(AppleBaseRequestParams):
    """Обертка над запросом для получения результата поиска с сайта apple"""

    def __init__(
        self,
        cookies: dict,
        location: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.BASE_URL + location,
            cookies=cookies,
            *args,
            **kwargs,
        )
