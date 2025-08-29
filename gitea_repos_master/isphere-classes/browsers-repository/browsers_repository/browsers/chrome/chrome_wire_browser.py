import logging
from copy import deepcopy

from selenium.webdriver.chrome.service import Service
from seleniumwire import webdriver

from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.config.settings import (
    EXPLICIT_WAIT,
    EXPLICIT_WAIT_FOR_LINK,
    IMPLICITLY_WAIT,
    MAX_PAGE_LOAD_TIMEOUT,
)


class ChromeWireBrowser(CommonBrowser):
    """Chrome браузер, версия wire.
    Имеет встроенный прокси, который сохраняет все запросы браузера
    и предоставляет к ним доступ.
    """

    EXPLICIT_WAIT = EXPLICIT_WAIT
    EXPLICIT_WAIT_FOR_LINK = EXPLICIT_WAIT_FOR_LINK
    IMPLICITLY_WAIT = IMPLICITLY_WAIT
    MAX_PAGE_LOAD_TIMEOUT = MAX_PAGE_LOAD_TIMEOUT

    def __init__(
        self,
        options: webdriver.ChromeOptions,
        service: Service,
        window_size: tuple | None = None,
        proxy_options: dict | None = None,
        proxy_options_wire: dict | None = None,
        logger=logging,
    ) -> None:
        """Конструктор класса.

        :param options: Настройки браузера параметров options.
        :param service: Настройки браузера параметров service (расположение geckodriver).
        :param window_size: Размер окна браузера.
        :param proxy_options: Настройки прокси, возвращается методом CommonBrowser.get_proxy()
            и обеспечивает совместимость.
        :param proxy_options_wire: Настройки прокси в формате для использования seleniumwire.
        """
        self._driver: webdriver.Chrome | None = None
        self.options = options
        self.service = service
        self.window_size = window_size
        self.current_proxy = proxy_options
        self.proxy_options_wire = proxy_options_wire
        self.logging = logger

    def start_browser(self) -> None:
        """Запускает браузер.

        :return: None
        """
        self.driver = webdriver.Chrome(
            # по аналогии с undetected chromedriver копируем опции и service
            service=deepcopy(self.service),
            options=deepcopy(self.options),
            # не позволяет переиспользовать словарь (меняет его), потому используем копию
            seleniumwire_options=deepcopy(
                self.proxy_options_wire,
            ),
        )
        if self.window_size:
            self.driver.set_window_size(*self.window_size)
        self.driver.set_page_load_timeout(self.MAX_PAGE_LOAD_TIMEOUT)
        self.driver.implicitly_wait(self.IMPLICITLY_WAIT)
        self.is_started = True
        self.logging.info("Chrome wire browser started")

    def close_browser(self) -> None:
        """Закрывает браузер.

        :return: None
        """
        if self.driver:
            self.driver.quit()
            self.driver = None
        self.is_started = False
        self.logging.info("Chrome wire browser closed")
