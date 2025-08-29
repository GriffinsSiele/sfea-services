from src.request_params.interfaces.base import RequestParams


class CreateTaskAPI(RequestParams):
    URL = '/dkbm-web-1.0/policyInfo.htm'
    METHOD = 'POST'

    def __init__(self, search_data, register_date, captcha_token, proxy=None):
        super().__init__(proxy)

        self.search_data = search_data
        self.register_date = register_date
        self.captcha_token = captcha_token

    def get_headers(self):
        return {
            **super().get_headers(),
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Sec-Fetch-Dest': 'empty',
            'Sec-Fetch-Mode': 'cors',
            'Sec-Fetch-Site': 'same-origin',
            'X-Requested-With': 'XMLHttpRequest',
        }

    def get_payload(self):
        return {
            'bsoseries': 'CCC',
            'bsonumber': None,
            'isBsoRequest': False,
            'captcha': self.captcha_token,
            'requestDate': self.register_date,
            **self.search_data,
        }
