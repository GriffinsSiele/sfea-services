"""
Модуль содержит класс SamsungBrowser, который расширяет возможности класса FirefoxBrowser
для работы с конкретным сайтом, а именно account.samsung.com.

Особенность работы сайта account.samsung.com:
1. При загрузке главной страницы, сначала загружается промежуточная страница,
аз-за чего функционал selenium драйвера, основанный на implicitly_wait
отрабатывает до завершения главной страницы.
Метод `load_main_page_waiting` корректно ожидает загрузку главной страницы сайта.

2. До ввода данные в поле поиска, кнопка поиска не активна.
При этом JavaScript код может отрабатывать с существенной задержкой (в том числе из-за его подгрузки) до 3 секунд.
Метод `wait_hidden_element_and_click` ожидает пока скрытый элемент станет доступным.

3. Метод `result_waiting` ожидает получение результата.
"""

import time

from selenium.common import TimeoutException
from selenium.webdriver import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait

from src.browser.browser import FirefoxBrowser
from src.config.app import ConfigApp
from src.logger.context_logger import logging
from src.utils import ExtStr, elapsed_time


class SamsungBrowser(FirefoxBrowser):
    """Расширяет функционал базового класса FirefoxBrowser для работы с сайтом account.samsung.com"""

    def load_page_waiting(self, target_waiting_element: tuple[By, str]) -> None:
        """Ожидает загрузку страницы сайта

        :param target_waiting_element: Элемент, по которому можно определить, что страница загружена.
        :return: None
        """
        multiple_wait = ConfigApp.auth.MULTIPLE_WAIT or 1
        while multiple_wait > 0:
            try:
                WebDriverWait(
                    self.driver, self.explicit_wait_delay, poll_frequency=0.1
                ).until(lambda res: self.driver.find_element(*target_waiting_element))
                break
            except TimeoutException:
                multiple_wait -= 1
                if multiple_wait > 0:
                    logging.info(f"Additional wait, {multiple_wait} iterations left.")
                    continue
                logging.warning("Timed out waiting for script completed")

    def wait_element(self, element: tuple[By, str]) -> None:
        """Ожидает пока элемент станет доступным на странице (будет загружен)

        :param element: Элемент, загрузку которого ждем.
        :return: None
        """
        self._waiting(element, f'Timed out waiting for element "{element}".')

    def wait_hidden_element_and_click(
        self, hidden: tuple[By, str], clicked: tuple[By, str]
    ) -> None:
        """Ожидает пока скрытый элемент станет доступным

        :param hidden: Скрытый, не активный элемент, изменение состояния которого необходимо дождаться.
        :param clicked: Элемент по которому необходимо кликнуть.
        :return: None
        """
        self.driver.implicitly_wait(0)
        try:
            WebDriverWait(
                self.driver, self.explicit_wait_for_link, poll_frequency=0.1
            ).until_not(lambda res: self.driver.find_element(*hidden))
        except TimeoutException:
            logging.warning(f'Timed out waiting for element "{hidden}" to be enabled.')

        self.driver.implicitly_wait(self.implicitly_wait)

        try:
            super().get_element_and_click_unsafe(*clicked)
            logging.info("Click element worked")
        except Exception as e:
            logging.warning(
                f'Failed to click element "{clicked}", exception {ExtStr(e).short()}'
            )
            self.click_element(clicked)

    def click_element(self, clicked: tuple[By, str]) -> None:
        """Выполняет клик мыши по элементу, используя ActionChains.

        :param clicked: Элемент по которому необходимо кликнуть.
        :return: None.
        """
        element = self.get_element(*clicked)
        if not element:
            logging.warning(f'ActionChains not worked for element "{clicked}"')
            return None

        ActionChains(self.driver).click(element).perform()
        logging.info(f'ActionChains worked for element "{clicked}"')

    def result_waiting(self, target_element: tuple[By, str]) -> None:
        """Ожидает получение результата

        :param target_element: Элемент, по которому можно определить, что результат получен.
        :return: None
        """
        self._waiting(target_element, "Timed out waiting for result.")

    def _waiting(self, target_element: tuple[By, str], timeout_msg: str) -> None:
        """Ожидает появление элемента на странице

        :param target_element: Элемент, который ожидаем.
        :param timeout_msg: Сообщение для таймаута ожидания.
        :return: None
        """
        self.driver.implicitly_wait(0)
        try:
            WebDriverWait(
                self.driver, self.explicit_wait_for_link, poll_frequency=0.1
            ).until(lambda res: self.driver.find_element(*target_element))
        except TimeoutException:
            logging.warning(timeout_msg)
        self.driver.implicitly_wait(self.implicitly_wait)

    def wait_recaptcha_to_load(self):
        """Ожидает загрузку JS скрипта reCAPTCHA. Если скрипт не загружен,
        не срабатывает поиск и происходит ошибка получения сессии.

        :return: None
        """
        start = time.time()
        try:
            request = self.driver.wait_for_request(ConfigApp.RECAPTCHA_URL, timeout=6)
            if not request.response.status_code:
                logging.warning("Error loading reCAPTCHA")
            logging.info(f"reCAPTCHA wait time {elapsed_time(start)} seconds.")
        except TimeoutException:
            logging.warning("Timeout waiting for reCAPTCHA")
