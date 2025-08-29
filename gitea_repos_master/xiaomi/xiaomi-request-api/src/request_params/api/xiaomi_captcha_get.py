from src.config import ConfigApp
from src.request_params.interfaces.xiaomi_main import XiaomiMainRequestParams
from src.utils.utils import get_timestamp


class XiaomiCaptchaGet(XiaomiMainRequestParams):
    """Обертка над запросом для получения изображения с капчей"""

    def __init__(
        self,
        e_data: str,
        *args,
        **kwargs,
    ):
        self.timestamp = get_timestamp()
        super().__init__(
            url=ConfigApp.GET_CAPTCHA_IMG_URL,
            data={"e": e_data},
            *args,
            **kwargs,
        )
