from src.config import ConfigApp
from src.request_params.interfaces.apple_base_requests import AppleBaseRequestParams


class AppleCaptchaGet(AppleBaseRequestParams):
    """Обертка над запросом для получения капчи (в формате base64) с сайта apple по текущей сессии"""

    def __init__(self, *args, **kwargs):
        super().__init__(
            url=ConfigApp.CAPTCHA_IMAGE_URL,
            params={"captchaType": "IMAGE"},
            *args,
            **kwargs,
        )
        self.headers = {
            **self.headers,
            "Content-Type": "application/json",
            "Cache-Control": "no-cache",
            "Pragma": "no-cache",
            # "X-Apple-I-FD-Client-Info": ""
            # "sstt": ""
        }
