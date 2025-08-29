from src.logic.misc.signature import Signature
from src.request_params.interfaces.redirect import CustomRedirect


class SignedParams(CustomRedirect):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    @property
    def query(self):
        return self._query

    @query.setter
    def query(self, value):
        secret = Signature().sign(self.url, value)
        self._query = {**value, "r": secret}
