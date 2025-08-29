from src.request_params.interfaces.base import RequestParams


class SessionGetParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(params={"login": "yes"}, path="/personal/", *args, **kwargs)
