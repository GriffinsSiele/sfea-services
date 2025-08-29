from src.config import ConfigApp
from src.request_params.interfaces.xiaomi_result import XiaomiResultRequestParams


class XiaomiSearchResult(XiaomiResultRequestParams):
    """Обертка над запросом для получения результата поиска с сайта xiaomi"""

    def __init__(
        self,
        search_data: str,
        token: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.RESULT_URL,
            cookies={
                "uLocale": "ru_RU",
                "vToken": token,
            },
            data={
                "userType": "PH",
                "qs": "",
                "id": search_data,
                "passToken": "",
                "passport_ph": "",
                "sid": "passport",
            },
            *args,
            **kwargs,
        )
