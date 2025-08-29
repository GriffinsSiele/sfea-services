import requests
import hashlib
import uuid
from pydash import get
from .provider import SMSProvider


class RedSMS(SMSProvider):
    def __init__(self, login, api_key):
        self.login = login
        self.api_key = api_key
        self.url = 'https://cp.redsms.ru/api/message'

    def __str__(self):
        return 'RedSMS'

    def send_request(self, phone):
        ts = str(uuid.uuid4())
        secret = hashlib.md5(f'{ts}{self.api_key}'.encode()).hexdigest()
        headers = {'login': self.login, 'ts': ts, 'secret': secret}
        data = {'route': 'hlr', 'to': phone, 'format': 'json'}
        res = requests.post(url=self.url, data=data, headers=headers)
        return self.check_response(res.json())

    def check_response(self, playload):
        if 'error_message' in playload.keys():
            return {
                "status": "Error",
                "code": 500,
                "message": get(playload, 'errors.0.message', playload['error_message']),
                "records": []
            }
