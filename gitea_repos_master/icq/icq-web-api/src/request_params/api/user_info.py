import calendar
import time
from random import randrange

from src.request_params.interfaces.base import RequestParams


class UserInfoParams(RequestParams):
    URL = '/api/v78/rapi/getUserInfo'

    def __init__(self, token, user_id, proxy=None):
        super().__init__(proxy)
        self.token = token
        self.user_id = user_id

    def req_id(self):
        id = randrange(99999)
        timestamp = calendar.timegm(time.gmtime())
        return f'{id}-{timestamp}'

    def get_payload(self):
        return {
            'reqId': self.req_id(),
            'aimsid': self.token,
            'params': {
                'sn': self.user_id,
            },
        }
