from src.config import ConfigApp
from src.request_params.interfaces.xiaomi_main_encrypt import (
    XiaomiMainEncryptRequestParams,
)
from src.utils.utils import get_timestamp


class XiaomiMainGet(XiaomiMainEncryptRequestParams):
    """Обертка над запросом для получения URL адреса с сайта xiaomi
    который содержит параметры строки, необходимые для получения капчи.
    """

    def __init__(
        self,
        *args,
        **kwargs,
    ):
        self.timestamp = get_timestamp()

        super().__init__(
            url=ConfigApp.BASE_URL,
            *args,
            **kwargs,
        )
