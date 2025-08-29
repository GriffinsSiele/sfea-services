from src.request_params.interfaces.base import RequestParams


class SearchParams(RequestParams):
    def __init__(self, phone, *args, **kwargs):
        params = {"phone": phone[1:], "personTypeId": "21020"}
        super().__init__(params=params, path="/portal/api/v1/user_info", *args, **kwargs)
