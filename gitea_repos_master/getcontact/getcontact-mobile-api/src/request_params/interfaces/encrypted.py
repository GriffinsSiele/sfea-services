from datetime import datetime
import logging
import json

from src.cipher.aes import CipherAES
from src.request_params.interfaces.base import RequestParams


class EncryptedParams(RequestParams):
    def __init__(self, aes_key, timestamp=None, proxy=None):
        super().__init__(proxy)
        self.aes_key = aes_key
        self.timestamp = timestamp if timestamp else int(round(datetime.now().timestamp()))

        self.cipher = CipherAES(aes_key)

    def __create_signature(self):
        return self.cipher.create_signature(self.__prepared_data(), self.timestamp)

    def __prepared_data(self):
        return json.dumps(self._get_payload()).replace(" ", "").replace("~", " ")

    def get_headers(self):
        return {
            **super().get_headers(), "X-Req-Timestamp": self.timestamp,
            "X-Encrypted": "1",
            'X-Req-Signature': self.__create_signature()
        }

    def get_payload(self):
        return json.dumps({'data': self.cipher.encrypt_AES_b64(self.__prepared_data())})

    def request(self):
        response = super().request()
        try:
            response_json = response.json()
            if 'data' in response_json:
                return self.cipher.decrypt_AES_b64(response_json['data'])
            return response_json
        except Exception as e:
            logging.error(e)
            return response
