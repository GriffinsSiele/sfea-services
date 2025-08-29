from src.config import settings
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaServicePost(CaptchaServiceBaseRequestParams):
    def __init__(
        self,
        provider: str,
        source: str,
        timeout: int,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=settings.CAPTCHA_SERVICE_URL + "/api/decode/image",
            params={"provider": provider, "source": source, "timeout": timeout},
            *args,
            **kwargs,
        )
        self.method = "POST"
        if timeout:
            self.timeout = timeout + 5
