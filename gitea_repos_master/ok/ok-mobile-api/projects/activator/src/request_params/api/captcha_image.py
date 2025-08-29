from lib.src.request_params.interfaces.base import RequestParams


class CaptchaImage(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://m.ok.ru"
        self.path = "/cap"
