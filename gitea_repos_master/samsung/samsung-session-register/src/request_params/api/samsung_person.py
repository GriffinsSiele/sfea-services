from src.config.app import ConfigApp
from src.interfaces import AbstractSamsungSource
from src.request_params.interfaces.base import BaseRequestParams


class SamsungSourcePerson(BaseRequestParams, AbstractSamsungSource):
    """Получение данных с сайта Samsung для пролонгации сессии person"""

    def __init__(
        self,
        search_data: dict,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.person.SESSION_URL,
            json={
                "givenName": search_data["first_name"],
                "familyName": search_data["last_name"],
                "birthDate": search_data["birthdate"],
            },
            *args,
            **kwargs,
        )
