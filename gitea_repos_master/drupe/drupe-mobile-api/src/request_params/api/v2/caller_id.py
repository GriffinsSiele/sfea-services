from src.config.app import APPLICATION_ID, PACKAGE_NAME, DEVICE_INFO
from src.generator.keys import AESParamsGenerator
from src.request_params.interfaces.crypted import CryptedParams


class CallerId(CryptedParams):
    URL = '/api/caller_id/caller_id/v2'
    METHOD = 'POST'

    def __init__(self, access_token, phone_number, proxy=None):
        aes_key = AESParamsGenerator.generate_key()
        iv_key = AESParamsGenerator.generate_iv()

        super().__init__(aes_key, iv_key, proxy)

        self.access_token = access_token
        self.phone_number = phone_number

    def _get_payload(self):
        return {
            'phone': self.phone_number,
            'action': 'search',
            'ACCESS_TOKEN': self.access_token,
            'APPLICATION_ID': APPLICATION_ID,
            'X-device-info': DEVICE_INFO,
            'package_name': PACKAGE_NAME
        }
