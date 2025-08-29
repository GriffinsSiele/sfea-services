import pathlib

import undetected_chromedriver as uc
from isphere_exceptions.source import SourceOperationFailure
from putils_logic.putils import PUtils
from selenium.common.exceptions import (
    ElementClickInterceptedException,
    InvalidSelectorException,
    NoAlertPresentException,
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
    WebDriverException,
)
from selenium.webdriver import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from src.browser.proxy.proxy_extension import ProxyExtension
from src.browser.selenium_utils import webdriver_exception_handler
from src.config.settings import (
    CHROME_VERSION,
    EXPLICIT_WAIT,
    EXPLICIT_WAIT_FOR_LINK,
    IMPLICITLY_WAIT,
)
from src.interfaces.abstract_browser import AbstractBrowser
from src.interfaces.abstract_extension import AbstractProxyExtension
from src.logger import logging

_current_path = pathlib.Path(__file__).parent.absolute()
_root_path = PUtils.bp(_current_path, "..", "..")


class SeleniumBrowser(AbstractBrowser):
    proxy_extension: AbstractProxyExtension = ProxyExtension()

    def __init__(self):
        self.driver = None
        self.is_started = False
        self.options = uc.ChromeOptions()
        self.headless = True
        self.window_size = [1024, 768]
        self.implicitly_wait_delay = IMPLICITLY_WAIT
        self.explicit_wait_delay = EXPLICIT_WAIT  # время ожидания отработки js скрипта

    @webdriver_exception_handler
    def start_browser(self) -> None:
        """Запускает браузер."""
        self.options.headless = self.headless
        self.driver: uc.Chrome = uc.Chrome(
            version_main=CHROME_VERSION,
            options=self.options,
            driver_executable_path=PUtils.bp(
                _root_path, "driver", f"chromedriver_{CHROME_VERSION}"
            ),
            use_subprocess=not self.headless,
        )
        self.driver.set_window_size(*self.window_size)
        self.driver.implicitly_wait(self.implicitly_wait_delay)
        self.is_started = True
        logging.debug("Selenium web driver started.")

    @webdriver_exception_handler
    def close_browser(self) -> None:
        """Завершает работу web драйвера."""
        if self.driver:
            self.driver.quit()
            self.is_started = False
            self.options = uc.ChromeOptions()
            logging.debug("Selenium web driver closed.")

    def get(self, url: str) -> None:
        """
        Переходит по переданному url
        и ждет загрузки страницы.

        :param url: url-адрес страницы.
        """
        try:
            self.driver.get(url)
        except WebDriverException:
            raise SourceOperationFailure()

    @property
    @webdriver_exception_handler
    def page_source(self) -> str:
        """Возвращает текущую html-страницу.

        :return: html код страницы в формате str.
        """
        return self.driver.page_source

    @webdriver_exception_handler
    def get_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает web-элемент по заданным параметрам поиска или None,
        если элемент не найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None
        """
        try:
            return self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            return None

    @webdriver_exception_handler
    def get_element_and_clear(self, by: By, selector: str) -> WebElement | None:
        if element := self.get_element(by, selector):
            element.clear()
            return element
        return None

    @webdriver_exception_handler
    def get_loaded_element(self, by: By, selector: str) -> WebElement | None:
        self.driver.implicitly_wait(0)
        result = None
        try:
            result = self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            pass
        finally:
            self.driver.implicitly_wait(self.implicitly_wait_delay)
        return result

    @webdriver_exception_handler
    def get_element_and_click(self, by: By, selector: str) -> None:
        """
        Находит элемент на странице и переходит по нему (click).
        Имеет задержку для ожидания обновления страницы.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        """
        try:
            element = WebDriverWait(self.driver, EXPLICIT_WAIT_FOR_LINK).until(
                EC.element_to_be_clickable((by, selector))
            )
            element.click()
            self._script_completed_waiting()
        except TimeoutException:
            logging.warning("Timed out waiting for element to be clickable.")
        except ElementClickInterceptedException:
            logging.warning(
                "The Element Click command could not be completed because the element "
                "receiving the events is obscuring the element that was requested to be clicked."
            )
        except StaleElementReferenceException:
            # элемент был найден, но когда пришло время по нему кликнуть, элемента уже нет
            logging.warning("Stale element not found.")

    @webdriver_exception_handler
    def get_element_and_set_data(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """
        Находит элемент на странице и вводит в него данные.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        :param data: данные для ввода.
        """
        element = None
        try:
            element = self.driver.find_element(by, selector)
            element.send_keys(data)
        except NoSuchElementException:
            logging.warning(f'Element "{selector}" not found.')
        return element

    @webdriver_exception_handler
    def get_element_set_data_and_enter(self, by: By, selector: str, data: str) -> None:
        """
        Находит элемент на странице, вводит данные и отправляет сигнал нажатия Enter.
        Имеет задержку для ожидания обновления страницы.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        :param data: данные для ввода.
        """
        element = self.get_element_and_set_data(by, selector, data)
        if element:
            element.send_keys(Keys.ENTER)
            self._script_completed_waiting()

    @webdriver_exception_handler
    def get_element_as_bytes(self, by: By, selector: str) -> bytes | None:
        """Находит элемент на странице, и возвращает его в формате png или None,
        если элемент не найден.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        :return: данные для ввода.
        """
        if element := self.get_element(by, selector):
            return element.screenshot_as_png
        return None

    @webdriver_exception_handler
    def accept_alert_if_exist(self) -> None:
        try:
            wait = WebDriverWait(self.driver, EXPLICIT_WAIT)
            wait.until(EC.alert_is_present(), "waiting for alert")
            alert = self.driver.switch_to.alert
            alert.accept()
        except TimeoutException:
            logging.warning("Timeout waiting for alert")

        try:
            self._script_completed_waiting()
            alert = self.driver.switch_to.alert
            alert.accept()
        except NoAlertPresentException:
            logging.warning("NoSuchElementException for alert")

    @webdriver_exception_handler
    def _script_completed_waiting(self) -> None:
        """Ожидает завершения работы js скрипта."""
        try:
            WebDriverWait(self.driver, self.explicit_wait_delay).until(
                lambda x: x.execute_script("return document.readyState") == "complete"
            )
        except TimeoutException:
            logging.warning("Timed out waiting for script completed outer")

    @webdriver_exception_handler
    def get_current_url(self) -> str:
        """Возвращает текущий URL"""
        try:
            return self.driver.current_url
        except WebDriverException:
            logging.warning("Error getting current URL")
            return ""

    def __del__(self) -> None:
        """Завершает работу web драйвера при удалении экземпляра класса."""
        self.close_browser()
