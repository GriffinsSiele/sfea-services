from abc import abstractmethod

import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from undetected_chromedriver import WebElement

from src.interfaces.abstract_extension import AbstractProxyExtension
from src.interfaces.utils import SingletonABCMeta

ScreenResolution = list[int]


class AbstractBrowser(metaclass=SingletonABCMeta):
    proxy_extension: AbstractProxyExtension
    driver = uc.Chrome
    is_started: bool
    browser_prepared: bool
    options: uc.ChromeOptions
    headless: bool
    window_size: ScreenResolution
    implicitly_wait_delay: float
    explicit_wait_delay: float

    @abstractmethod
    def start_browser(self) -> None:
        pass

    @abstractmethod
    def close_browser(self) -> None:
        """Завершает работу web драйвера."""
        raise NotImplementedError()

    @abstractmethod
    def get(self, url: str) -> None:
        """
        Переходит по переданному url
        и ждет загрузки страницы.

        :param url: url-адрес страницы.
        """
        pass

    @property
    @abstractmethod
    def page_source(self) -> str:
        """Возвращает текущую html-страницу.

        :return: html код страницы в формате str.
        """
        pass

    @abstractmethod
    def get_element(self, by: By, selector: str) -> WebElement:
        pass

    @abstractmethod
    def get_element_and_clear(self, by: By, selector: str) -> WebElement:
        pass

    @abstractmethod
    def get_loaded_element(self, by: By, selector: str) -> WebElement:
        pass

    @abstractmethod
    def get_element_and_click(self, by: By, selector: str) -> None:
        """
        Находит элемент на странице и переходит по нему (click).
        Имеет задержку для ожидания обновления страницы.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        """
        ...

    def get_element_and_set_data(self, by: By, selector: str, data: str) -> None:
        """
        Находит элемент на странице и вводит в него данные.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        :param data: данные для ввода.
        """

    @abstractmethod
    def get_element_set_data_and_enter(self, by: By, selector: str, data: str) -> None:
        """
        Находит элемент на странице, вводит данные и отправляет сигнал нажатия Enter.
        Имеет задержку для ожидания обновления страницы.

        :param by: локатор, определяет стратегию поиска.
        :param selector: ключ поиска.
        :param data: данные для ввода.
        """
        pass

    @abstractmethod
    def get_current_url(self) -> str:
        """Возвращает текущий URL"""
        pass
