from src.logic.cipher.cnonce import CNonceGenerator
from src.request_params.interfaces.signed import SignedParams


class CNoncedParams(SignedParams):
    def __init__(self, cnonce=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        cnonce = cnonce if cnonce else CNonceGenerator.get_random()
        self.query = {**self.query, "cnonce": cnonce}
