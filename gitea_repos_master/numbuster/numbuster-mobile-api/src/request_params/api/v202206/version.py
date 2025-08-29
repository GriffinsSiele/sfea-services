from src.request_params.interfaces.authed import AuthedParams


class VersionParams(AuthedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(path="/api/v202206/apk/version", *args, **kwargs)
