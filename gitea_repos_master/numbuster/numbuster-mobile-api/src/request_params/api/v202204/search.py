import random

from src.request_params.interfaces.authed import AuthedParams


class SearchParams(AuthedParams):
    def __init__(self, phone_number: str, source=None, *args, **kwargs):
        source = source if source else random.choice(["CALL_LOG", "MAIN_SEARCH"])
        super().__init__(
            path=f"/api/v202204/search/{phone_number}",
            query={
                "paidSearch": "0",
                "order_by": "interesting",
                "source": source,
                "locale": "ru",
            },
            *args,
            **kwargs,
        )
