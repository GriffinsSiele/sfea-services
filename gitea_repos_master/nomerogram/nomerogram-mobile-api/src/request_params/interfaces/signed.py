from src.logic.cipher import SecretCipher
from src.request_params.interfaces.base import RequestParams


class SignedParams(RequestParams):
    def __init__(self, query, proxy=None):
        super().__init__(proxy)

        self.query = query

    def get_query(self):
        secret = SecretCipher.get_secret(self.query)
        return {**self.query, 'secret': secret}
