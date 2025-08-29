from urllib.parse import urlencode

from lib.src.request_params.interfaces.base import RequestParams


class CaptchaSolve(RequestParams):
    def __init__(self, token, captcha_text, *args, **kwargs):
        super().__init__(
            *args,
            **kwargs,
            data=urlencode(
                {
                    "fr.code": captcha_text,
                    "rfr.posted": "set",
                    "verify_captcha": "Готово",
                }
            ),
        )
        self.domain = "https://m.ok.ru"
        self.path = "/dk"
        self.query = {
            "bk": "VerifyCaptchaEnter",
            "st.cmd": "verifyCaptchaEnter",
            "_prevCmd": "verifyCaptchaEnter",
            "__dp": "y",
            "tkn": token,
        }
        self.headers["Content-Type"] = "application/x-www-form-urlencoded"
