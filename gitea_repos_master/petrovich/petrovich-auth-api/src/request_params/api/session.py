from src.request_params.interfaces.base import RequestParams


class SessionParams(RequestParams):
    def __init__(self, *args, **kwargs):

        super().__init__(
            url="https://api.petrovich.ru/session/v3/init.js",
            method="GET",
            *args,
            **kwargs,
        )
        self.timeout = 15
