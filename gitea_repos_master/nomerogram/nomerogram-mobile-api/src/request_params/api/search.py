from src.config.app import APP_ID
from src.request_params.interfaces.signed import SignedParams


class SearchParams(SignedParams):
    URL = '/api/v1.1/group/list'

    def __init__(self, query, cookies={}, proxy=None):
        super().__init__(proxy)
        self.query = self.update_query(query)

        self.cookies = cookies

    def update_query(self, query):
        return {
            **query,
            'from': 'search',
            'version': '1',
            'app_id': APP_ID,
        }

    def get_query(self):
        return super().get_query()

    def get_cookies(self):
        return self.cookies
