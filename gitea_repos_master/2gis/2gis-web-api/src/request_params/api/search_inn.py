from src.config.app import ConfigApp
from src.request_params.interfaces.signed import SignedParams


class SearchINN(SignedParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/2.0/catalog/branch/list"

    def set_query(self, inn, extra_query=None):
        self.query = {
            "itin": inn,
            "key": ConfigApp.APP_KEY,
            **(extra_query if extra_query else {}),
        }
