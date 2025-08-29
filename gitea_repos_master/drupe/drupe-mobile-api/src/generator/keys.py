import os

from src.transform.hex import HexConverter


class AESParamsGenerator:
    @staticmethod
    def _random_bytes(size=16):
        return HexConverter.hex(os.urandom(size)).upper().decode()

    @staticmethod
    def generate_key():
        return AESParamsGenerator._random_bytes()
        # return '58 65 B2 A5 37 0B CF 57 4C 0E 10 00 8C E0 78 DE'.replace(' ', '')

    @staticmethod
    def generate_iv():
        return AESParamsGenerator._random_bytes()
        # return 'CE E9 85 D2 CD EA AC CC 98 DA FC 6C C8 37 8D 8F'.replace(' ', '')
