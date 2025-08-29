from src.request_params.interfaces.base import RequestParams


class Auth2GIS(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.domain = "https://2gis.ru/"
