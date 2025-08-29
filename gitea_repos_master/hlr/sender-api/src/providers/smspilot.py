from pydash import get
import requests
from .provider import SMSProvider


class SMSPilot(SMSProvider):
    def __init__(self, callback_address, api_key):
        self.callback_address = callback_address
        self.api_key = api_key
        self.url = 'https://smspilot.ru/api.php'

    def __str__(self):
        return 'SMSPilot'

    def send_request(self, phone):
        data = {'send': 'HLR', 'to': phone, 'callback': self.callback_address, 'apikey': self.api_key, 'format': 'json'}
        res = requests.post(url=self.url, data=data)
        return self.check_response(res.json())

    def check_response(self, playload):
        if 'error' in playload.keys():
            return {
                "status": "Error",
                "code": get(playload, 'error.code'),
                "message": get(playload, 'error.description_ru'),
                "records": []
            }
