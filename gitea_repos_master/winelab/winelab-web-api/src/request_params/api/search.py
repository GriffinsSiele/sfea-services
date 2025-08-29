from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, payload, *args, **kwargs):
        field = "email" if "@" in payload else "phone"
        params = {field: payload}
        super().__init__(
            params=params, path=f"/my-account/check-{field}", *args, **kwargs
        )
