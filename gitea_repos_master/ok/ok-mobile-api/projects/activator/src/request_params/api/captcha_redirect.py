from lib.src.request_params.interfaces.base import RequestParams


class CaptchaRedirect(RequestParams):
    def __init__(self, token, device, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://m.ok.ru"
        self.path = "/dk"
        self.method = "GET"

        self.query = {
            "st.cmd": "verifyCaptchaStart",
            "tkn": token,
            "__dp": "y",
            "_prevCmd": "externalVerify",
        }

        self.headers["x-statid"] = str(device)
