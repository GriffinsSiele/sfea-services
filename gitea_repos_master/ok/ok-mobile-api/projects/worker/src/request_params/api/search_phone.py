from src.request_params.interfaces.search import SearchParams


class SearchPhoneParams(SearchParams):

    def __init__(self, phone_number, *args, **kwargs):
        super().__init__(
            credentials={"phone": phone_number},
            *args,
            **kwargs,
        )
