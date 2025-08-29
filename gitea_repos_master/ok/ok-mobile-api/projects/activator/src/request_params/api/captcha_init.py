from lib.src.request_params.interfaces.base import RequestParams


class CaptchaInit(RequestParams):
    def __init__(self, recovery_token, device, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://m.ok.ru"
        self.path = "/dk"
        self.method = "GET"

        self.query = {
            "st.cmd": "externalVerify",
            "st.recoveryToken": recovery_token,
            "app.params": "x",
        }
        self.redirect = False

        self.cookies = {
            "APPCAPS": "android_8_23.9.25|pay|unauth",
            "theme": "main",
            "APP_DCAPS": "dpr^2.75|vw^392|sw^392|",
            "X-statid": str(device),
            "installerPackageName": "com.android.vending",
        }

        self.headers["x-statid"] = str(device)
