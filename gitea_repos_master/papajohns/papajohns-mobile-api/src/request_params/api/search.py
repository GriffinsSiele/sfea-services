import random
import string

from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, phone, device_token, password=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/user/login"

        phone = phone if phone.startswith("+") else "+" + phone

        random_password = "".join(random.choice(string.hexdigits) for _ in range(10))
        password = password if password else random_password

        self.payload = {
            "phone": phone,
            "password": password,
            "city_id": "1",
            "lang": "ru",
            "device_token": device_token,
            "platform": "Android",
        }
