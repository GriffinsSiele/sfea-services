from src.config.app import ConfigApp
from src.request_params.interfaces.base import RequestParams


class HostResolvedParams(RequestParams):
    def __init__(self, domain=None, *args, **kwargs):
        super().__init__(
            domain=domain if domain else ConfigApp.BASE_HOST, *args, **kwargs
        )
