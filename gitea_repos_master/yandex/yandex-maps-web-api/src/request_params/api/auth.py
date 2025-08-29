from src.request_params.interfaces.signed import SignedParams


class AuthParams(SignedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/maps/"
