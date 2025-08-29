from src.config.settings import CAPTCHA_SERVICE_URL
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaSolverCreate(CaptchaServiceBaseRequestParams):
    def __init__(
        self,
        provider: str,
        source: str,
        timeout: int,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=CAPTCHA_SERVICE_URL + "/api/decode/image",
            params={"provider": provider, "source": source, "timeout": timeout},
            *args,
            **kwargs,
        )
        self.method = "POST"
        if timeout:
            self.timeout = timeout + 5
