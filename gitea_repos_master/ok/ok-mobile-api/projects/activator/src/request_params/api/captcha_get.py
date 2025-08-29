from urllib.parse import urlencode

from lib.src.request_params.interfaces.base import RequestParams


class CaptchaGet(RequestParams):
    def __init__(self, token, device, *args, **kwargs):
        super().__init__(
            *args,
            **kwargs,
            data=urlencode(
                {
                    "rfr.posted": "set",
                    "accept_profile": "Ок, понятно!",
                }
            ),
        )
        self.domain = "https://m.ok.ru"
        self.path = "/dk"
        self.method = "POST"

        self.query = {
            "bk": "VerifyCaptchaStart",
            "st.cmd": "verifyCaptchaStart",
            "_prevCmd": "verifyCaptchaStart",
            "tkn": token,
        }

        self.headers["Content-Type"] = "application/x-www-form-urlencoded"
        self.headers["x-statid"] = str(device)
