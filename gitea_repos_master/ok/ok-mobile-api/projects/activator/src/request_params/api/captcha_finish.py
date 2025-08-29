from urllib.parse import urlencode

from lib.src.request_params.interfaces.base import RequestParams


class CaptchaFinish(RequestParams):
    def __init__(self, token, *args, **kwargs):
        super().__init__(
            *args,
            **kwargs,
            data=urlencode({"rfr.posted": "set", "finish_captcha_verify": "Log in"}),
        )
        self.domain = "https://m.ok.ru"
        self.path = "/dk"

        self.query = {
            "bk": "VerifyCaptchaSuccess",
            "st.cmd": "verifyCaptchaSuccess",
            "_prevCmd": "verifyCaptchaSuccess",
            "tkn": token,
        }
        self.headers["Content-Type"] = "application/x-www-form-urlencoded"
