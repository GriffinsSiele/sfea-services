import json
from urllib.parse import urlencode

from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.base import RequestParams


class AuthParams(RequestParams):
    def __init__(self, login, password, device, *args, **kwargs):
        super().__init__(
            *args, **kwargs, data=urlencode(self._get_payload(login, password, device))
        )
        self.path = "/api/batch/executeV2"
        self.headers["Content-Type"] = "application/x-www-form-urlencoded"

    def _get_payload(self, login, password, device):
        auth_method = {
            "auth.login": {
                "params": {
                    "deviceId": str(device),
                    "gen_token": True,
                    "password": password,
                    "user_name": login,
                    "verification_supported": True,
                    "verification_supported_v": "5",
                }
            }
        }
        return {
            "application_key": ConfigApp.APP_KEY,
            "id": "auth.login",
            "methods": json.dumps([auth_method]),
        }
