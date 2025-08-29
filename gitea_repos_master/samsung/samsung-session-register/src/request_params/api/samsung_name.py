from src.config.app import ConfigApp
from src.interfaces import AbstractSamsungSource
from src.request_params.interfaces.base import BaseRequestParams


class SamsungSourceName(BaseRequestParams, AbstractSamsungSource):
    """Получение данных с сайта Samsung для пролонгации сессии name"""

    def __init__(
        self,
        params: dict,
        headers: dict,
        search_data: dict,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.name.SESSION_URL,
            json={
                "recoveryId": search_data["account_login"],
                "givenName": search_data["first_name"],
                "familyName": search_data["last_name"],
                "birthDate": search_data["birthdate"],
            },
            params={"v": params},
            *args,
            **kwargs,
        )
        self.headers = headers
