from lib.src.request_params.interfaces.authed import AuthedParams


class SearchParams(AuthedParams):
    def __init__(self, phone_number, *args, **kwargs):
        super().__init__(
            url="https://search5-noneu.truecaller.com/v2/search", *args, **kwargs
        )
        self.method = "GET"
        self.headers = {
            **self.headers,
            "Host": "search5-noneu.truecaller.com",
        }
        self.query = {
            "q": phone_number,
            "countryCode": "RU",
            "encoding": "json",
            "type": "10",
        }
