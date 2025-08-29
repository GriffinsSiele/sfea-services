from requests_logic.base import RequestBaseParamsAsync


class BaseRequestParams(RequestBaseParamsAsync):
    """Общие настройки для работы с сетью"""

    DEFAULT_HEADERS: dict

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.method = "POST"
        self.timeout = 5
        self.verify = False
