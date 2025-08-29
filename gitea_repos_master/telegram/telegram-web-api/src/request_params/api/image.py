from src.request_params.interfaces.base import RequestParams


class ImageParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
