from src.request_params.interfaces.authed import AuthedParams


class TokenParams(AuthedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(path="/api/v202207/web/make/token", *args, **kwargs)
