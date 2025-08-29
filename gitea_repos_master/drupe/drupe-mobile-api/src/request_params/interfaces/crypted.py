import json

from src.request_params.interfaces.base import RequestParams
from src.transform.aes import AESCrypt
from src.transform.base64 import Base64
from src.transform.compress import CompressData
from src.transform.hex import HexConverter
from src.transform.rsa import RSACipher


class CryptedParams(RequestParams):
    def __init__(self, aes_key, aes_iv, proxy=None):
        super().__init__(proxy)
        self.aes_key = aes_key
        self.aes_iv = aes_iv

        self.headers = {}

    def sign_request(self):
        rsa = RSACipher()
        header_raw = rsa.encrypt(HexConverter.unhex(self.aes_key))
        self.headers = {'X-CallerIdSdk-Key': Base64.encode(header_raw)}

    def crypt_payload(self):
        payload = self._get_payload()
        json_raw = json.dumps(payload).replace(' ', '').encode()

        compressed = CompressData.compress(json_raw)

        aes = AESCrypt(self.aes_key, self.aes_iv)
        crypted = aes.encrypt(compressed)

        self.payload = crypted + HexConverter.unhex(self.aes_iv)

    def get_headers(self):
        return {**super().get_headers(), **self.headers}

    def get_payload(self):
        return self.payload

    def _get_payload(self):
        return {}

    def request(self):
        self.sign_request()
        self.crypt_payload()
        return super().request()
