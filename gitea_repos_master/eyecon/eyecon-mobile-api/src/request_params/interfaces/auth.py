from src.request_params.interfaces.base import RequestParams


class AuthParams(RequestParams):
    def __init__(self, e_auth, e_auth_c, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers["e-auth"] = e_auth
        self.headers["e-auth-c"] = str(e_auth_c)
