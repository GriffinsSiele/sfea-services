from src.config.app import ConfigApp
from src.request_params.interfaces.auth import AuthParams


class SearchParams(AuthParams):
    def __init__(self, phone, *args, **kwargs):
        params = {
            "cli": phone,
            "lang": "ru,en",
            "is_callerid": "true",
            "is_ic": "true",
            "cv": ConfigApp.APP_VERSION,
            "requestApi": "okHttp",
            "source": "OnBoardingView",
        }
        super().__init__(params=params, path="/app/getnames.jsp", *args, **kwargs)
        self.headers["content-type"] = "application/x-www-form-urlencoded; charset=utf-8"
