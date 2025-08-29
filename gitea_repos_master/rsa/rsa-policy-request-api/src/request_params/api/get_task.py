from src.request_params.interfaces.base import RequestParams


class GetTaskAPI(RequestParams):
    URL = '/dkbm-web-1.0/policyInfoData.htm'
    METHOD = 'POST'

    def __init__(self, process_id, search_data, register_date, proxy=None):
        super().__init__(proxy)

        self.process_id = process_id
        self.search_data = search_data
        self.register_date = register_date

    def get_headers(self):
        accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'

        return {
            **super().get_headers(),
            'Accept': accept,
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'same-origin',
            'Sec-Fetch-User': '?1',
            'Upgrade-Insecure-Requests': '1',
        }

    def get_payload(self):
        return {
            'processId': self.process_id,
            'bsoseries': 'ССС',
            'bsonumber': '',
            **self.search_data,
            'requestDate': self.register_date,
            'driversInfo[0].surname': '',
            'driversInfo[0].name': '',
            'driversInfo[0].patronymic': '',
            'driversInfo[0].birthday': self.register_date,
            'driversInfo[0].driverSerial': '',
            'driversInfo[0].driverNumber': '',
        }
