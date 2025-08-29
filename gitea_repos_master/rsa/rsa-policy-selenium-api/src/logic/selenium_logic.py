import logging
import random
import time

from request_logic.exceptions import ProxyBlocked, SeleniumBrokenProcess, InCorrectData, ProxyLocked, NoDataError, \
    UnknownError
from selenium.common import TimeoutException, WebDriverException
from selenium.webdriver import ActionChains, Keys
from selenium.webdriver.common.by import By

from src.auto_register.human_behaviour import HumanBehaviour
from src.auto_register.selenium import SeleniumDriver
from src.logic.enum import field_to_id, SeleniumState


class SeleniumLogic:
    URL = "https://dkbm-web.autoins.ru/dkbm-web-1.0/policyInfo.htm"

    def __init__(self, headless=True, proxy=None):
        self.proxy = proxy
        self.headless = headless

        self.create_driver()

        self._status = SeleniumState.CREATED
        self.count_captcha_detected = 0

    def create_driver(self):
        self.selenium_driver = SeleniumDriver(headless=self.headless, proxy=self.proxy, randomize_client=False)
        self.human_behaviour = HumanBehaviour(self.selenium_driver.driver)

    def prepare(self):
        start_time = time.time()
        try:
            self.selenium_driver.driver.get(SeleniumLogic.URL)
            logging.info('Page loaded')
        except TimeoutException:
            self._status = SeleniumState.BAN
            raise ProxyBlocked('Нет доступа к сайту. Вероятно, прокси заблокирован')
        except WebDriverException:
            self._status = SeleniumState.BAN
            raise ProxyBlocked('Нет доступа к сайту. Прокси заблокирован точно')

        self.human_behaviour.wait(1)

        form = self.selenium_driver.get_tag(By.ID, 'requestForm')

        if not form and 'Bad' in self.selenium_driver.driver.page_source:
            self._status = SeleniumState.BAN
            raise ProxyBlocked('Нет доступа к сайту. Вероятно, прокси заблокирован')

        self.human_behaviour.random_move(form)

        tab = self.selenium_driver.get_tag(By.ID, 'tsBlockTab')
        if not tab:
            self._status = SeleniumState.UNKNOWN

            error = 'Не найден таб на переключение в режим поиска.'
            logging.error(error)
            raise SeleniumBrokenProcess(error)

        self.human_behaviour.click(tab)

        end_time = time.time()
        logging.info(f'Preparing done: {round(end_time - start_time, 2)} sec.')
        self._status = SeleniumState.READY

    def search(self, field, value):
        start_time = time.time()

        try:
            response = self._search(field, value)
        except Exception as e:
            self._status = SeleniumState.UNKNOWN
            self._log_time(start_time)
            raise e

        self._log_time(start_time)
        return response

    def _log_time(self, start_time):
        end_time = time.time()
        logging.info(f'Search done: {round(end_time - start_time, 2)} sec.')

    def _search(self, field, value):
        self._status = SeleniumState.IN_PROGRESS

        field_tag = self.selenium_driver.get_tag(By.ID, field_to_id[field])
        if not field_tag:
            self._status = SeleniumState.UNKNOWN
            raise InCorrectData(f'Не найдено поле ввода данных: [{field}={field_to_id[field]}]')

        self.human_behaviour.click(field_tag)
        self.human_behaviour.wait(1)

        self.human_behaviour.type(field_tag, value)
        self.human_behaviour.wait(1)

        form = self.selenium_driver.get_tag(By.ID, 'requestForm')
        self.human_behaviour.random_move(form)

        self.human_behaviour.scroll()
        self.human_behaviour.wait(1)

        search_button = self.selenium_driver.get_tag(By.ID, 'buttonFind')
        if not search_button:
            raise SeleniumBrokenProcess('Не найдена кнопка поиска')

        self.human_behaviour.click(search_button)

        table = None
        counter, delay = 0, 10
        while counter < delay:
            table = self.selenium_driver.get_tag(By.CLASS_NAME, 'policies-tbl', log_error=False)
            if table:
                break

            page_src = self.selenium_driver.driver.page_source
            if 'Отказ в предоставлении' in page_src:
                self.count_captcha_detected += 1
                raise ProxyLocked(f'Не прошли капчу. Попытка: {self.count_captcha_detected}')
            if self.count_captcha_detected > 6:
                raise ProxyBlocked('Превышен порог непрохождения капчи подряд')

            if 'Некорректно указаны данные' in page_src:
                self.count_captcha_detected = 0
                raise InCorrectData('Введенные данные некорректны по мнению сайта')
            if 'Сведения о договоре ОСАГО с указанными данными не найдены.' in page_src:
                self.count_captcha_detected = 0
                raise NoDataError('Данные не найдены')

            counter += 0.2
            time.sleep(0.2)

        if not table:
            raise UnknownError('Время ожидания таблицы закончилось, однако ее нет на странице')

        self._status = SeleniumState.CREATED
        self.count_captcha_detected = 0
        return self.selenium_driver.driver.page_source

    def simulate(self):
        decision = random.choice([i for i in range(4)])

        if decision == 0:
            self.human_behaviour.wait(2)
        if decision == 1:
            self.human_behaviour.scroll(is_down=random.choice([True, False]))
            self.human_behaviour.wait(2)

        if decision == 2:
            form = self.selenium_driver.get_tag(By.TAG_NAME, 'html')
            ActionChains(self.selenium_driver.driver).key_down(Keys.CONTROL).perform()
            self.human_behaviour.wait(0.1)
            form.send_keys('f')
            self.human_behaviour.wait(0.1)
            ActionChains(self.selenium_driver.driver).key_up(Keys.CONTROL).perform()
            self.human_behaviour.wait(0.1)
            self.human_behaviour.type(form, random.choice(['Запре', 'ГАЗ', 'Страх', 'Кат']))
            self.human_behaviour.wait(1)

        if decision == 3:
            form = self.selenium_driver.get_tag(By.ID, 'requestForm')
            self.human_behaviour.random_move(form)
            self.human_behaviour.wait(1)

    @property
    def status(self):
        return self._status
