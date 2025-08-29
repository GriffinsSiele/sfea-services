from src.config.app import ConfigApp
from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, token, phones, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/service/4.0/user/authenticated/get_caller_name_dec"
        self.headers = {**self.headers, "Authorization": f"bearer {token}"}

        phones = phones if isinstance(phones, list) else [phones]
        self.payload = {
            "address_book_country_code": "RU",
            "sim_country_code": "RU",
            "app_name": "Simpler",
            "app_version": ConfigApp.APP_VERSION,
            "context": 1,
            "phone_numbers": [
                {"index": i, "number": number} for i, number in enumerate(phones)
            ],
        }
