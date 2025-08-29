from typing import Optional

from src.request_params.interfaces.cnonced import CNoncedParams


class AuthedParams(CNoncedParams):
    def __init__(self, access_token: Optional[str] = None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.query = {**self.query, "access_token": access_token}
