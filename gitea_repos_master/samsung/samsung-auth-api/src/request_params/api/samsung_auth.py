from src.config import ConfigApp
from src.interfaces import AbstractSamsungSource
from src.request_params.interfaces.base import BaseRequestParams


class SamsungSourceAuth(BaseRequestParams, AbstractSamsungSource):
    """Получение данных с сайта Samsung"""

    def __init__(
        self,
        search_data: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.BASE_URL_AUTH,
            json={"account": search_data},
            *args,
            **kwargs,
        )
