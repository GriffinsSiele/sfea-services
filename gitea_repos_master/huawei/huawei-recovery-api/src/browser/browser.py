"""
Модуль содержит класс для работы с браузером FireFox.
"""

import pathlib
from typing import Type

from isphere_exceptions.worker import InternalWorkerError
from putils_logic import PUtils
from selenium.common import (
    ElementClickInterceptedException,
    InvalidSelectorException,
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
)
from selenium.webdriver.common.by import By
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.webdriver.support.wait import WebDriverWait
from seleniumwire import webdriver

from src.config.app import ConfigApp
from src.config.settings import (
    BROWSER_VERSION,
    EXPLICIT_WAIT,
    EXPLICIT_WAIT_FOR_LINK,
    IMPLICITLY_WAIT,
)
from src.interfaces.abstract_seleniumwire_proxy import AbstractSeleniumWireProxy
from src.logger.context_logger import logging
from src.proxy import SeleniumWireProxy

_current_path = pathlib.Path(__file__).parent.absolute()
_root_path = PUtils.bp(_current_path, "..", "..")


class FirefoxBrowser:
    """Класс для работы с браузером FireFox"""

    proxy_service: Type[AbstractSeleniumWireProxy] = SeleniumWireProxy
    implicitly_wait = IMPLICITLY_WAIT
    explicit_wait_delay = EXPLICIT_WAIT
    explicit_wait_for_link = EXPLICIT_WAIT_FOR_LINK

    def __init__(self, headless: bool = False) -> None:
        """Конструктор класса

        :param headless: Режим headless (по умолчанию False)
        """
        self._driver: webdriver.Firefox | None = None
        self.options: webdriver.FirefoxOptions | None = None
        self.seleniumwire_proxy_options: dict | None = None
        self.service = Service(
            executable_path=PUtils.bp(
                _root_path, "driver", f"geckodriver_{BROWSER_VERSION}"
            )
        )
        self.headless = headless

    async def start_browser(self) -> None:
        """Запускает браузер

        :return: None
        """
        self.options = webdriver.FirefoxOptions()
        self.options.set_preference("intl.accept_languages", "ru")
        self.seleniumwire_proxy_options = await self.proxy_service().get_proxy()
        if self.headless:
            self.options.add_argument("-headless")
        self.driver = webdriver.Firefox(
            service=self.service,
            options=self.options,
            seleniumwire_options=self.seleniumwire_proxy_options,
        )
        self.driver.set_page_load_timeout(ConfigApp.MAX_PAGE_LOAD_TIMEOUT)
        self.driver.implicitly_wait(self.implicitly_wait)

    def close_browser(self) -> None:
        """Закрывает браузер

        :return: None
        """
        if self.driver:
            self.driver.close()
            self.driver.quit()
            self.driver = None
        logging.info("Browser closed")

    def get(self, url: str) -> None:
        """Загружает страницу по переданному url

        :param url: URL страницы для загрузки
        :return: None
        """
        if not self.driver:
            logging.error("Browser not started")
            return None
        self.driver.get(url)
        self._script_completed_waiting()

    @property
    def page_source(self) -> str:
        """Возвращает текущую html-страницу.

        :return: html код страницы в формате str.
        """
        return self.driver.page_source

    def get_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает web-элемент по переданным параметрам

        :param by: Стратегия поиска элемента (по ID, TAG_NAME, XPATH ...)
        :param selector: Параметры поиска
        :return: :class:`selenium.webdriver.remote.webelement.WebElement` или None, если элемент не найден.
        """
        if not self.driver:
            return None
        try:
            return self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            logging.warning(f'Element "{selector}" not found.')
            return None

    def get_loaded_element(self, by: By, selector: str) -> WebElement | None:
        self.driver.implicitly_wait(0)
        result = None
        try:
            result = self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            pass
        finally:
            self.driver.implicitly_wait(self.implicitly_wait)
        return result

    def get_element_and_set_data(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит web-элемент и устанавливает для него переданные данные (data) методом send_keys

        :param by: Стратегия поиска элемента (по ID, TAG_NAME, XPATH ...)
        :param selector: Параметры поиска
        :param data: Данные для передачи элементу
        :return: :class:`selenium.webdriver.remote.webelement.WebElement` или None, если элемент не найден.
        """
        element = self.get_element(by, selector)
        if not element:
            return None

        try:
            element.send_keys(data)
            return element
        except StaleElementReferenceException:
            logging.warning(f'Stale element "{by}:{selector}" not found.')
        return None

    def get_select_element_and_set_data(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит web-элемент с выпадающим списком и устанавливает для него переданное видимое значение (data)

        :param by: Стратегия поиска элемента (по ID, TAG_NAME, XPATH ...)
        :param selector: Параметры поиска
        :param data: Видимое значение элемента
        :return: :class:`selenium.webdriver.remote.webelement.WebElement` или None, если элемент не найден.
        """
        element = self.get_element(by, selector)
        if not element:
            logging.warning(f'Select element not found "{by}: {selector}"')
            return None
        try:
            select = Select(element)
            select.select_by_visible_text(data)
        except NoSuchElementException:
            logging.warning(f'Select element value not found "{data}"')

        return element

    def get_element_and_click_unsafe(self, by: By, selector: str) -> None:
        """Находит web-элемент и выполняет click по нему.
        Не обрабатывает исключения, который могут возникнуть в процессе работы метода.

        :param by: Стратегия поиска элемента (по ID, TAG_NAME, XPATH ...)
        :param selector: Параметры поиска
        :return: None
        """
        element = WebDriverWait(self.driver, self.explicit_wait_for_link).until(
            EC.element_to_be_clickable((by, selector))
        )
        element.click()
        self._script_completed_waiting()

    def get_element_and_click(self, by: By, selector: str) -> None:
        """Находит web-элемент и выполняет click по нему.
        Обрабатывает и логирует исключения TimeoutException,
        ElementClickInterceptedException, StaleElementReferenceException.

        :param by: Стратегия поиска элемента (по ID, TAG_NAME, XPATH ...)
        :param selector: Параметры поиска
        :return: None
        """
        try:
            self.get_element_and_click_unsafe(by, selector)
        except TimeoutException:
            logging.warning(
                f'Timed out waiting for element "{by}:{selector}" to be clickable.'
            )
        except ElementClickInterceptedException:
            logging.warning(
                f'The Element Click command could not be completed because the element "{by}:{selector}"'
                "receiving the events is obscuring the element that was requested to be clicked."
            )
        except StaleElementReferenceException:
            # элемент был найден, но когда пришло время по нему кликнуть, элемента уже нет
            logging.warning(f'Stale element "{by}:{selector}" not found.')

    def _script_completed_waiting(self) -> None:
        """Ожидает завершения работы js скрипта

        :return: None
        """
        try:
            WebDriverWait(self.driver, self.explicit_wait_delay).until(
                lambda x: x.execute_script("return document.readyState") == "complete"
            )
        except TimeoutException:
            logging.warning("Timed out waiting for script completed outer")

    def clean_all_cookies(self) -> None:
        """Очищает куки браузера

        :return: None
        """
        if self.driver:
            self.driver.delete_all_cookies()

    @property
    def driver(self) -> webdriver.Firefox:
        if not self._driver:
            raise InternalWorkerError("Driver not defined")
        return self._driver

    @driver.setter
    def driver(self, value) -> None:
        self._driver = value

    def __del__(self) -> None:
        """Закрывает браузер, при удалении экземпляра класса FirefoxBrowser

        :return: None
        """
        try:
            self.close_browser()
        except InternalWorkerError:
            pass
