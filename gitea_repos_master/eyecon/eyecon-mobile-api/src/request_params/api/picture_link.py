from src.config.app import ConfigApp
from src.request_params.interfaces.auth import AuthParams


class PictureLinkParams(AuthParams):
    def __init__(self, phone, *args, **kwargs):
        params = {
            "cli": phone,
            "is_callerid": "true",
            "size": "big",
            "type": "0",
            "src": "MenifaFragment",
            "cancelfresh": "0",
            "cv": ConfigApp.APP_VERSION,
        }
        super().__init__(
            params=params, path="/app/pic", allow_redirects=False, *args, **kwargs
        )
        self.headers["content-type"] = "application/x-www-form-urlencoded; charset=utf-8"
