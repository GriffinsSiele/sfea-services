from src.logic.yandex.signature import Signature
from src.request_params.interfaces.base import RequestParams


class SignedParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    @property
    def query(self):
        return self._query

    @query.setter
    def query(self, value):
        secret = Signature().sign(value)
        self._query = {**value, "s": secret}
