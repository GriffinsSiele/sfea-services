import json
import logging
import time

from bs4 import BeautifulSoup
from pydash import get
from recaptcha_token.extractor import TokenExtractor, CaptchaVersion
from recaptcha_token.mongo import MongoTokenAPI
from request_logic.exceptions import InCorrectData, UnknownError

from settings import WITH_GENERATION_TOKEN, MONGO_TOKEN_DB, MONGO_TOKEN_PORT
from src.adapter.keydb import KeyDBAdapter
from src.logic.enum import SearchFields, field_to_validator, SeleniumState
from src.logic.selenium_logic import SeleniumLogic
from src.logic.parse import parseTable


class SessionManager:
    def __init__(self, headless=True, proxy=None):
        self.headless = headless
        self.proxy = proxy
        logging.info(f'Using proxy: {proxy}')

        self.sl = SeleniumLogic(self.headless, self.proxy)

        self.prepare_force = False
        self.is_generate_token = False
        self.mongo = MongoTokenAPI(MONGO_TOKEN_DB, MONGO_TOKEN_PORT) if WITH_GENERATION_TOKEN else None

    def search(self, data):
        try:
            data = json.loads(data)
        except Exception as _:
            raise InCorrectData('Ожидается json')

        field = get(data, 'type')
        if field not in [s.value for s in SearchFields]:
            raise InCorrectData('Неверно указа тип для поиска')

        field = SearchFields(field)
        value = get(data, 'value')

        is_ok, error = field_to_validator[field](value)
        if not is_ok:
            raise InCorrectData(f'Ошибка валидации поля: {error}')

        return self._process_search(field, value)

    def prepare_selenium(self):
        if self.prepare_force or self.sl.status != SeleniumState.READY:
            self.sl.prepare()
            self.prepare_force = False

        if self.is_generate_token:
            self.generate_token()
            self.is_generate_token = False

    def _process_search(self, field, value):
        self.prepare_selenium()

        try:
            page_src = self.sl.search(field, value)
        except Exception as e:
            self.prepare_force = True
            raise e

        if not page_src:
            raise UnknownError('Поиск не отработал')

        soup = BeautifulSoup(page_src, features="lxml")
        table = soup.find('table')
        if not table:
            raise UnknownError('Не найдена таблица на странице')

        try:
            response = parseTable(table)
            return KeyDBAdapter.toKeyDB(response)
        except Exception as e:
            raise UnknownError(f'Во время разбора страницы произошла ошибка: {e}')

    def simulate(self):
        return self.sl.simulate()

    def generate_token(self):
        if not WITH_GENERATION_TOKEN:
            return
        start_time = time.time()
        try:
            extractor = TokenExtractor(self.sl.selenium_driver.driver, version=CaptchaVersion.V3, debug=True)
            extractor.action = 'submit'

            token = extractor.extract()
            if token:
                self.mongo.add_v3(token, extractor.sitekey, extractor.action)
            end_time = time.time()
            logging.info(f'Extracting token done: {round(end_time - start_time, 2)} sec.')
        except Exception as e:
            logging.error(f'Error during getting token: {e}')

    def generate_token_on_start(self):
        self.is_generate_token = WITH_GENERATION_TOKEN
