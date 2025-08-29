import urllib.parse
from datetime import datetime

from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, payload, token, *args, **kwargs):
        params = {
            "localDate": datetime.now().strftime("%Y-%m-%dT%H:%M:%S"),
            "addressType": "REGULAR",
        }
        data = urllib.parse.urlencode({"address": payload})
        super().__init__(
            path="/mobile-api/method/8.6/address.human.suggest",
            method="POST",
            params=params,
            data=data,
            *args,
            **kwargs,
        )
        self.headers["MobileApiAccessToken"] = token
