from requests_logic.base import RequestBaseParamsRequests


class BaseRequestParams(RequestBaseParamsRequests):
    """Общие настройки для работы с сетью"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.method = "POST"
        self.timeout = 5
        self.verify = False
