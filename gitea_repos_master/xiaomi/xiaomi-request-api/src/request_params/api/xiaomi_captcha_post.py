from src.config import ConfigApp
from src.request_params.interfaces.xiaomi_main import XiaomiMainRequestParams
from src.utils.utils import get_timestamp


class XiaomiCaptchaPost(XiaomiMainRequestParams):
    """Обертка над запросом для отправки решения капчи на сайт xiaomi"""

    def __init__(
        self,
        captcha_solution: str,
        captcha_token: str,
        e_data: str,
        *args,
        **kwargs,
    ):
        self.timestamp = get_timestamp()
        super().__init__(
            url=ConfigApp.POST_CAPTCHA_IMG_URL,
            data={"code": captcha_solution, "token": captcha_token, "e": e_data},
            *args,
            **kwargs,
        )
