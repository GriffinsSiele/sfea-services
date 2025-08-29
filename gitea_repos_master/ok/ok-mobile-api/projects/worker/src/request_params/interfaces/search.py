import json

from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, session_key, credentials, *args, **kwargs):
        super().__init__(
            *args, **kwargs, data=self._get_payload(session_key, credentials)
        )
        self.path = "/api/search/byContactsBook"

    def _get_payload(self, session_key, credentials):
        query = json.dumps(
            {
                "credentials": [credentials],
                "simCardsInfo": [
                    {
                        "isoCountry": "ru",
                    }
                ],
            }
        )

        return {
            "application_key": ConfigApp.APP_KEY,
            "__screen": "feed_main",
            "session_key": session_key,
            "fields": "*",
            "query": query,
        }
