from lib.src.request_params.interfaces.base import RequestParams


class AuthedParams(RequestParams):
    def __init__(self, installation_id: str, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "authorization": "Bearer " + installation_id,
        }
