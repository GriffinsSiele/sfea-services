from src.request_params.interfaces.base import RequestParams


class UsernameParams(RequestParams):
    def __init__(self, username: str, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = f"/{username}"
