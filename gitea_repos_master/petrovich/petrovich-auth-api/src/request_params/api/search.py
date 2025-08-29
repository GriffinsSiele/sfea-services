from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, payload, payload_type, *args, **kwargs):
        params = {
            "pet_case": "camel",
            "city_code": "spb",
            "client_id": "pet_site",
        }
        json_data = {
            "type": payload_type,
            "login": payload,
        }
        super().__init__(
            path="/user/v1.1/login/check",
            method="POST",
            params=params,
            json=json_data,
            *args,
            **kwargs,
        )
