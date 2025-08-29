import requests

from src.config.config import APP_ID, APP_VERSION


class RegisterParams(object):
    METHOD = 'POST'
    URL = '/service/4.0/firebase_login_v2'

    def __init__(self, device, fcm_token, hash_list):
        self.device = device
        self.fcm_token = fcm_token
        self.hash_list = hash_list

    def generate(self):
        return self.get_link(), self.get_headers(), self.get_payload(), self.METHOD

    def get_headers(self):
        return {
            "Content-Type": "application/json; charset=UTF-8",
            "Connection": "close",
            "Accept-Encoding": "gzip, deflate",
            "User-Agent": "okhttp/4.6.0"
        }

    def get_base_url(self):
        return 'https://mobileapps.getsimpler.me:443'

    def get_link(self):
        return self.get_base_url() + self.URL

    def get_payload(self):
        return {
            "country": "us",
            "language": "en",
            "app_id": APP_ID,
            "app_version": APP_VERSION,
            "hash_list": self.hash_list,
            "token": self.fcm_token,
            **(self.device.to_dict()),
        }

    def request(self):
        url, headers, data, method = self.generate()
        return requests.request(method, url, json=data, headers=headers)
