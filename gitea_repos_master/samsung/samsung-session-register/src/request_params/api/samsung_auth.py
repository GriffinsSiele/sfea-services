from src.config.app import ConfigApp
from src.interfaces import AbstractSamsungSource
from src.request_params.interfaces.base import BaseRequestParams


class SamsungSourceAuth(BaseRequestParams, AbstractSamsungSource):
    """Получение данных с сайта Samsung для пролонгации сессии auth"""

    def __init__(
        self,
        params: dict,
        headers: dict,
        search_data: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.auth.SESSION_URL,
            json={
                "loginId": search_data,
                "rememberId": False,
                "staySignIn": False,
            },
            params={"v": params},
            *args,
            **kwargs,
        )
        self.headers = headers
