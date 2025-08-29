from src.request_params.interfaces.authed import AuthedParams


class HistoryParams(AuthedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(path="/api/v202203/main/history", *args, **kwargs)
