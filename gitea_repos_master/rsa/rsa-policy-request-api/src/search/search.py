import json
import logging
from time import sleep, time

import requests
from bs4 import BeautifulSoup
from pydash import get
from request_logic.exceptions import UnknownError, InCorrectData, LimitError, AccountBlocked, NoDataError, ProxyBlocked

from src.adapter.keydb import KeyDBAdapter
from src.request_params.api.create_task import CreateTaskAPI
from src.request_params.api.get_task import GetTaskAPI
from src.search.enum import SearchFields, field_to_validator, field_to_id
from src.utils.parse import parse_table
from src.utils.utils import now

MAX_RETRIES_TOKEN = 8
MAX_RETRIES_PAGE = 5


class SearchAPI:
    def __init__(self, mongo, sitekey, action, proxy=None):
        self.proxy = proxy
        self.mongo = mongo

        self.sitekey = sitekey
        self.action = action

        self.retries_count_create = 0
        self.retries_count_create_parse = 0

        self.session = requests.Session()

    def search_json(self, data):
        try:
            data = json.loads(data)
        except Exception as _:
            raise InCorrectData('Ожидается json')

        return self.search(data)

    def search(self, data: object) -> object:
        self.retries_count_create, self.retries_count_create_parse = 0, 0

        field, value = self._validate_input(data)
        return self._process_search(self._prepare_payload(field, value))

    def _validate_input(self, data):
        field = get(data, 'type')
        if field not in [s.value for s in SearchFields]:
            raise InCorrectData('Неверно указа тип для поиска')

        field = SearchFields(field)
        value = get(data, 'value')

        is_ok, error = field_to_validator[field](value)
        if not is_ok:
            raise InCorrectData(f'Ошибка валидации поля: {error}')

        return field, value

    def _process_search(self, payload):
        start_time = time()
        process_id = self._step_1_search(payload)
        response = self._step_2_search(process_id, payload)
        logging.info(f'Search done: {round(time() - start_time, 2)} sec.')
        return response

    def _step_1_search(self, payload):
        """Добавление задачи на поиск в autoins с указанием капчи"""
        if self.retries_count_create > MAX_RETRIES_TOKEN:
            raise LimitError('Превышено количество попыток использования токенов')
        self.retries_count_create += 1

        try:
            return self._step_1_inner_logic(payload)
        except AccountBlocked as e:
            logging.error(f'Попытка создания задачи {self.retries_count_create}: {e}')
            return self._step_1_search(payload)

    def _step_1_inner_logic(self, payload):
        try:
            token_json = self.mongo.get_v3(self.sitekey, self.action)
            token = get(token_json, 'token')
        except Exception as e:
            raise UnknownError(f'MongoServer exception: {e}')

        if not token or get(token_json, 'status') == 204:
            raise LimitError('Нет токенов')

        try:
            r = CreateTaskAPI(payload, now(), token, self.proxy)
            r.set_session(self.session)
            response = r.request()
            response_json = response.json()
        except requests.exceptions.ConnectionError as e:
            self.mongo.add_v3(token, self.sitekey, self.action)
            raise ProxyBlocked(f'Proxy dead: {e}')
        except Exception as e:
            raise UnknownError(e)

        valid_captcha = get(response_json, 'validCaptcha', True)
        if not valid_captcha:
            raise AccountBlocked('Ошибка в капче: ' + get(response_json, 'errorMessage'))

        process_id = get(response_json, 'processId')
        if not process_id:
            raise AccountBlocked('Ошибка в получении process_id. Не найдено')

        return process_id

    def _step_2_search(self, process_id, payload):
        self.retries_count_create_parse += 1
        if self.retries_count_create_parse > MAX_RETRIES_PAGE:
            raise UnknownError('Превышено количество попыток ожидания')

        try:
            r = GetTaskAPI(process_id, payload, now(), self.proxy)
            r.set_session(self.session)
            response = r.request()
        except Exception as e:
            raise UnknownError(e)

        if 'html' not in response.text:
            sleep(0.5)
            return self._step_2_search(process_id, payload)

        return self.parse(response.text)

    def parse(self, page_src):
        if 'Сведения о договоре ОСАГО с указанными данными не найдены.' in page_src:
            raise NoDataError('Данные не найдены')

        soup = BeautifulSoup(page_src, features="lxml")
        table = soup.find('table')
        if not table:
            raise UnknownError('Не найдена таблица на странице')

        try:
            response = parse_table(table)
            return KeyDBAdapter.toKeyDB(response)
        except Exception as e:
            raise UnknownError(f'Во время разбора страницы произошла ошибка: {e}')

    def _prepare_payload(self, field, value):
        payload = {
            'vin': None,
            'licensePlate': None,
            'bodyNumber': None,
            'chassisNumber': None,
            field_to_id[field]: value
        }
        return payload
