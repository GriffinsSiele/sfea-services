from src.request_params.interfaces.base import BaseRequestParams


class CaptchaImageGet(BaseRequestParams):
    def __init__(
        self,
        url: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=url,
            *args,
            **kwargs,
        )
