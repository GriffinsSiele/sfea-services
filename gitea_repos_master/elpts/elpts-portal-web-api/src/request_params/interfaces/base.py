from requests_logic.base import RequestBaseParamsAsync


class BaseRequestParams(RequestBaseParamsAsync):
    DEFAULT_HEADERS: dict

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.method = "GET"
        self.timeout = 5
        self.verify = False
