from src.config.app import AppConfig
from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/callapp-server/csrch"

    def set_query(self, phone_number, id, token):
        self.query = {
            "cpn": phone_number if phone_number.startswith("+") else "+" + phone_number,
            "myp": id,
            "oes": "0",
            "ibs": "1",
            "cid": "1",
            "ispro": "1",
            "tk": token,
            "cvc": AppConfig.APP_VERSION,
            "ucr": "RU",
            "ulr": "RU",
        }
