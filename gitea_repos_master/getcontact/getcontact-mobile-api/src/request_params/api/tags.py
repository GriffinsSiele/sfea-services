from src.config.app import ConfigApp
from src.request_params.api.search import SearchParams


class TagsParams(SearchParams):
    URL = f'/{ConfigApp.API_VERSION}/number-detail'

    def __init__(
        self,
        phone_number,
        device_id,
        token,
        aes_key,
        timestamp=None,
        proxy=None,
        android_os='android 6.0',
    ):
        super().__init__(phone_number, device_id, token, aes_key, timestamp, proxy, android_os)

    def _get_payload(self):
        return {
            'countryCode': ConfigApp.COUNTRY,
            'source': 'details',
            'token': self.token,
            'phoneNumber': self.phone_number,
        }
