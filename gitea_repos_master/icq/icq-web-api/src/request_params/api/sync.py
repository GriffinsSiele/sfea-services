import calendar
import time
from random import randrange

from src.request_params.interfaces.base import RequestParams


class SyncParams(RequestParams):
    URL = '/api/v78/rapi/contacts/sync'

    def __init__(self, token, phone_number, proxy=None):
        super().__init__(proxy)
        self.token = token
        self.phone_number = phone_number

    def req_id(self):
        id = randrange(99999)
        timestamp = calendar.timegm(time.gmtime())
        return f'{id}-{timestamp}'

    def get_payload(self):
        return {
            "aimsid": self.token,
            "params": {
                "update": [{
                    "name": self.phone_number,
                    "phoneList": [self.phone_number]
                }]
            },
            "reqId": self.req_id()
        }
