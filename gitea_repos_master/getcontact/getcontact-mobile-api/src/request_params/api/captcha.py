import time

from src.config.app import ConfigApp
from src.request_params.interfaces.encrypted import EncryptedParams


class CaptchaParams(EncryptedParams):
    URL = f'/{ConfigApp.API_VERSION}/verify-code'

    def __init__(
        self,
        validation_code,
        device_id,
        token,
        aes_key,
        timestamp=None,
        proxy=None,
        android_os='android 6.0',
    ):
        timestamp = timestamp if timestamp else str(time.time()).split(".")[0]
        super().__init__(aes_key, timestamp, proxy)
        self.validation_code = validation_code
        self.token = token
        self.device_id = device_id
        self.android_os = android_os

    def _get_payload(self):
        return {
            'token': self.token,
            'validationCode': self.validation_code,
        }

    def get_headers(self):
        return {
            **super().get_headers(),
            "X-Token": self.token,
            "X-Os": self.android_os,
            "X-Client-Device-Id": self.device_id,
        }
