import random
import string
import urllib.parse
from datetime import datetime

from src.config.app import ConfigApp
from src.requet_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, login, device, password=None, *args, **kwargs):
        random_password = "".join(random.choice(string.hexdigits) for _ in range(12))
        password = password if password else random_password
        data = {"key": ConfigApp.APP_KEY, "login": login, "password": password}
        data = urllib.parse.urlencode(data)

        super().__init__(data=data, *args, **kwargs)

        self.path = "/api/11/auth"

        self.headers = {**self.headers, "user-agent": device.get("user_agent")}
        self.query = {"key": ConfigApp.APP_KEY}

        now = datetime.timestamp(datetime.now())
        self.cookies = {**self.cookies, "v": str(int(now))}
