import logging

from selenium.common.exceptions import (
    ElementClickInterceptedException,
    InvalidSelectorException,
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
    WebDriverException,
)
from selenium.webdriver import Chrome, Firefox, Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from browsers_repository.interfaces.abstract_browser import AbstractBrowser


class CommonBrowser(AbstractBrowser):
    """Общие методы для работы со всеми браузерами."""

    EXPLICIT_WAIT: int
    EXPLICIT_WAIT_FOR_LINK: int
    IMPLICITLY_WAIT: int
    MAX_PAGE_LOAD_TIMEOUT: int
    logging: logging.Logger
    driver: Firefox | Chrome
    current_proxy: dict | None
    is_started = False

    def start_browser(self) -> None:
        """Запускает браузер.
        Метод уникален для каждого типа браузера и должен быть
        реализован непосредственно в нем.

        :return: None.
        """
        raise NotImplementedError()

    def close_browser(self) -> None:
        """Завершает работу браузера.
        Метод уникален для каждого типа браузера и должен быть
        реализован непосредственно в нем.

        :return: None.
        """
        raise NotImplementedError()

    def get(self, url: str) -> bool:
        """Переходит по переданному url-адресу и ждет загрузку страницы
        в течение времени IMPLICITLY_WAIT (указывается в переменных окружения).

        :param url: URL-адрес страницы.
        :return: Результат перехода (True - успех, False - ошибка).
        """
        if not self.is_started:
            raise ValueError('Start the browser before using it ("start_browser" method)')
        try:
            self.driver.get(url)
        except WebDriverException:
            return False
        return True

    @property
    def page_source(self) -> str:
        """Возвращает текущую html-страницу.

        :return: html код страницы в формате str.
        """
        return self.driver.page_source

    def get_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает элемент по указанным параметрам поиска.
        Имеет задержку IMPLICITLY_WAIT (указывается в переменных окружения)
        на ожидание появления элемента. Если элемент не найден - вернет None.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        try:
            return self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            return None

    def get_loaded_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает элемент по указанным параметрам поиска.
        Метод не имеет задержки IMPLICITLY_WAIT (указывается в переменных окружения)
        на ожидание появления элемента, предполагается, что элемент загружен.
        Если элемент не найден - вернет None.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        self.driver.implicitly_wait(0)
        result = None
        try:
            result = self.driver.find_element(by, selector)
        except (NoSuchElementException, InvalidSelectorException):
            pass
        finally:
            self.driver.implicitly_wait(self.IMPLICITLY_WAIT)
        return result

    def get_element_and_click(self, by: By, selector: str) -> WebElement | None:
        """Находит элемент на странице и выполняет click по нему.
        Имеет задержку для ожидания обновления страницы.
        Возвращает найденный элемент или None, если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        element = None
        try:
            element = WebDriverWait(self.driver, self.EXPLICIT_WAIT_FOR_LINK).until(
                EC.element_to_be_clickable((by, selector))
            )
            element.click()
            self._script_completed_waiting()
        except TimeoutException:
            self.logging.warning("Timed out waiting for element to be clickable.")
        except ElementClickInterceptedException:
            self.logging.warning(
                "The Element Click command could not be completed because the element "
                "receiving the events is obscuring the element that was requested to be clicked."
            )
        except StaleElementReferenceException:
            # элемент был найден, но когда пришло время по нему кликнуть, элемента уже нет
            self.logging.warning("Stale element not found.")
        return element

    def get_element_and_set_data(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит элемент на странице и вводит данные.
        Целевой элемент должен обладать возможностью принимать данные
        (input, area, ...). Возвращает найденный элемент или None,
        если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :param data: Данные для ввода.
        :return: WebElement или None.
        """
        element = None
        try:
            element = self.driver.find_element(by, selector)
            element.send_keys(data)
        except NoSuchElementException:
            self.logging.warning(f'Element "{selector}" not found.')
        return element

    def get_element_and_clear(self, by: By, selector: str) -> WebElement | None:
        """Находит элемент на странице и очищает введенные данные
        (элементы типа input, area, ...). Возвращает найденный элемент или None,
        если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        if element := self.get_element(by, selector):
            element.clear()
            return element
        return None

    def get_element_set_data_and_enter(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит элемент на странице, вводит данные и отправляет сигнал нажатия Enter.
        Имеет задержку для ожидания обновления страницы.
        Возвращает найденный элемент или None, если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :param data: Данные для ввода.
        :return: WebElement или None.
        """
        element = self.get_element_and_set_data(by, selector, data)
        if element:
            element.send_keys(Keys.ENTER)
            self._script_completed_waiting()
        return element

    def _script_completed_waiting(self) -> None:
        """Ожидает завершения работы js скрипта."""
        try:
            WebDriverWait(self.driver, self.EXPLICIT_WAIT).until(
                lambda x: x.execute_script("return document.readyState") == "complete"
            )
        except TimeoutException:
            self.logging.warning("Timed out waiting for script completed outer")

    def waiting(self, by: By, selector: str, timeout: float) -> None:
        """Ожидает появление элемента на странице.
        По истечении времени ожидания возбуждает исключение TimeoutException.

        :param by: Локатор, определяет стратегию поиска элемента, появление которого ожидаем.
        :param selector: Ключ поиска.
        :param timeout: Максимальное время ожидания.
        :return: None
        """
        WebDriverWait(self.driver, timeout, poll_frequency=0.05).until(
            lambda res: self.driver.find_element(by, selector)
        )

    def waiting_safe(self, by: By, selector: str, timeout: float) -> bool:
        """Ожидает появление элемента на странице. Если элемент появился, немедленно завершает
        работу и возвращает True. Если время ожидания вышло, а элемент не появился -
        возвращает False.

        :param by: Локатор, определяет стратегию поиска элемента, появление которого ожидаем.
        :param selector: Ключ поиска.
        :param timeout: Максимальное время ожидания.
        :return: Результат ожидания, True - элемент появился, False - вышло время ожидания,
            элемент не появился.
        """
        try:
            self.waiting(by, selector, timeout)
        except TimeoutException:
            return False
        return True

    def waiting_element_becomes_unavailable(
        self, target_element: tuple[By, str], timeout: float
    ) -> bool:
        """Ожидает пока элемент станет недоступным на странице. Может быть использован для
        проверки изменений на странице в ходе выполнения JavaScript кода (Например, изменилось
        свойство кнопки и она стала доступной для клика, при этом в качестве аргумента передаем
        определение недоступной для клика кнопки).

        :param target_element: Элемент который должен измениться.
        :param timeout: Максимальное время ожидания.
        :return: True, элемент стал недоступен или False, если элемент все еще присутствует.
        """
        try:
            WebDriverWait(self.driver, timeout, poll_frequency=0.1).until_not(
                lambda res: self.driver.find_element(*target_element)
            )
            return True
        except TimeoutException:
            self.logging.warning(f"Timeout waiting {target_element}")
            return False

    def get_current_url(self) -> str:
        """Возвращает текущий URL.

        :return: Текущий URL.
        """
        try:
            return self.driver.current_url
        except WebDriverException:
            self.logging.warning("Error getting current URL")
            return ""

    def get_proxy(self) -> dict | None:
        """Возвращает прокси, который установлен для браузера.

        :return: Прокси.
        """
        return self.current_proxy

    def clean_all_cookies(self) -> None:
        """Очищает куки браузера.

        :return: None
        """
        if self.driver:
            self.driver.delete_all_cookies()
