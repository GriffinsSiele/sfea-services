from src.request_params.interfaces.base import BaseRequestParams


class CaptchaServiceBaseRequestParams(BaseRequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            "accept": "application/json",
        }
