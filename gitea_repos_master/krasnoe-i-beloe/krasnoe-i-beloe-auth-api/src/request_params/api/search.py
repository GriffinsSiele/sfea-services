from src.logic.utils.utils import random_string
from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, phone, password=None, *args, **kwargs):
        headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "Origin": "https://krasnoeibeloe.ru",
            "Referer": "https://krasnoeibeloe.ru/personal/",
        }
        data = {
            "form_id": "auth_form",
            "auth_type": "phone",
            "AUTH_FORM": "Y",
            "TYPE": "AUTH",
            "backurl": "/personal/",
            "phone": phone,
            "USER_PASSWORD": password or random_string(10),
            "USER_REMEMBER": "Y",
        }
        super().__init__(
            path="/personal/", method="POST", headers=headers, data=data, *args, **kwargs
        )
