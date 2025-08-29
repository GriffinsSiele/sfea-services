from src.request_params.interfaces.base import RequestParams


class FrontendParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(
            method="GET",
            *args,
            **kwargs,
        )
        self.domain = "https://petrovich.ru"
        self.timeout = 15
