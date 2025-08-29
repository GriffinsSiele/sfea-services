import re

import requests
import logging

from src.cipher.base64 import Base64


class CaptchaDecode:
    @staticmethod
    def decode_response(response):
        image_b64 = response["result"]["image"]
        image_data = Base64.decode(image_b64)
        return CaptchaDecode.decrypt(image_data)

    @staticmethod
    def decrypt(image):
        try:
            with requests.session() as session:
                data = {'image': ('1.jpg', image, 'image/jpeg')}
                s = session.post('http://10.8.0.1:8001/gcdecode/', files=data).json()

            return re.sub("[^A-Za-z0-9]", "", s['text'])
        except Exception as e:
            logging.error(e)
            return ''
