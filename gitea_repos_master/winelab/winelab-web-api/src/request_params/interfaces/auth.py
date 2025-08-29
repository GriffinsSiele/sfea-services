from src.request_params.interfaces.base import RequestParams


class AuthParams(RequestParams):
    def __init__(self, login, password, csrf, *args, **kwargs):
        data = {
            "isCheckout": "false",
            "isPassword": "true",
            "j_username": login,
            "j_password": password,
            "CSRFToken": csrf,
        }
        super().__init__(
            method="POST", path="/j_spring_security_check", data=data, *args, **kwargs
        )
