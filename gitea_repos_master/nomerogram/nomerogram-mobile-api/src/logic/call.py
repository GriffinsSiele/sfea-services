import logging
import time

from pydash import is_equal
from request_logic.exceptions import NoDataError, UnknownError, ProxyBlocked
from request_logic.proxy import ProxyManager

from settings import PROXY_LOGIN, PROXY_PASSWORD
from src.logic.adapter import ResponseAdapter
from src.logic.number_adapter import CarplateAdapter
from src.request_params.api.search import SearchParams


class SearchAPI:
    def __init__(self, device_id, cookies, with_random_proxy=False):
        self.device_id = device_id
        self.cookies = cookies

        self.proxy = ProxyManager({
            'login': PROXY_LOGIN,
            'password': PROXY_PASSWORD
        }).get_proxy() if with_random_proxy else {}

        self.timestamp = 0

    def search(self, carplate):
        logging.info(f'Search for carplate: {carplate}')
        start_time = time.time()
        response = self._search(carplate)
        end_time = time.time()
        logging.info(f'Search done: {round(end_time - start_time, 2)} sec')
        return response

    def _search(self, carplate):
        self.update_timestamp()

        carplate = CarplateAdapter.number_to_server(carplate)
        logging.info(f'Casted to {carplate}')

        payload = {'carplate': carplate, 'device_id': self.device_id, 'timestamp': self.timestamp}

        try:
            params = SearchParams(payload, self.cookies, self.proxy)
            response = params.request().json()
        except ConnectionError:
            raise ProxyBlocked('Вероятно, прокси заблокирован')
        except Exception as e:
            raise UnknownError(e)

        session = params.get_session()
        new_cookies = session.cookies.get_dict()
        self.cookies = new_cookies if new_cookies else self.cookies

        return self.adapter(response)

    def adapter(self, response):
        ra = ResponseAdapter()
        response = ra.cast(response)

        if is_equal(response, ResponseAdapter.EMPTY_RESPONSE):
            raise NoDataError('Ничего не найдено')

        return response

    def get_cookies(self):
        return self.cookies

    def update_timestamp(self):
        self.timestamp = int(time.time() * 100)
