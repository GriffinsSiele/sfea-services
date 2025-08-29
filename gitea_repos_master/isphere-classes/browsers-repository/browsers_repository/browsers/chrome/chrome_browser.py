import logging
from copy import deepcopy
from time import sleep

from selenium import webdriver
from selenium.webdriver.chrome.service import Service

from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.browsers.extensions.proxy.proxy_extension import ProxyExtension
from browsers_repository.config.settings import (
    EXPLICIT_WAIT,
    EXPLICIT_WAIT_FOR_LINK,
    IMPLICITLY_WAIT,
    MAX_PAGE_LOAD_TIMEOUT,
)


class ChromeBrowser(CommonBrowser):
    """Простой Chrome браузер."""

    proxy_extension_cls = ProxyExtension
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
        logger=logging,
    ) -> None:
        """Конструктор класса.

        :param options: Настройки браузера параметров options.
        :param service: Настройки браузера параметров service (расположение chromedriver).
        :param window_size: Размер окна браузера.
        :param proxy_options: Настройки прокси.
        :return: None.
        """
        self.driver: webdriver.Chrome | None = None
        self.is_started = False
        self.options = options
        self.service = service
        self.window_size = window_size
        self.current_proxy = proxy_options
        self.proxy_extension: ProxyExtension | None = None
        self.logging = logger

    def start_browser(self) -> None:
        """Запускает браузер.

        :return: None.
        """
        options = deepcopy(self.options)
        if self.current_proxy:
            self.proxy_extension = self.proxy_extension_cls()
            options.add_argument(f"--load-extension={self._load_proxy_extension()}")

        self.driver = webdriver.Chrome(options=options, service=deepcopy(self.service))
        if self.window_size:
            self.driver.set_window_size(*self.window_size)
        self.driver.set_page_load_timeout(self.MAX_PAGE_LOAD_TIMEOUT)
        self.driver.implicitly_wait(self.IMPLICITLY_WAIT)
        self.is_started = True
        if self.current_proxy:
            # время на загрузку и запуск proxy расширения
            # без данной задержки первая страница загрузится без прокси
            # срабатывает от 0.05 сек, время 0.1 выбрано с запасом
            sleep(0.1)
        self.logging.info("Chrome browser started")

    def close_browser(self) -> None:
        """Завершает работу браузера.

        :return: None.
        """
        if self.driver:
            self.driver.quit()
            self.driver = None
        self.proxy_extension = None
        self.is_started = False
        self.logging.info("Chrome browser closed.")

    def _load_proxy_extension(self) -> str:
        """Настраивает и размещает во временной папке расширение для браузера,
        которое обеспечивает передачу всех данных браузера через прокси сервер.

        :return: Путь к расширению.
        """
        if not self.current_proxy:
            raise ValueError("Proxy parameters not passed")

        if not self.proxy_extension:
            raise ValueError("Proxy extension not created")

        port = int(self.current_proxy.get("port", -1))
        if port == -1:
            raise ValueError(
                "The port must be a string with numbers in the range 0 - 65535"
            )

        self.proxy_extension.prepare(
            str(self.current_proxy.get("server")),
            port,
            str(self.current_proxy.get("login")),
            str(self.current_proxy.get("password")),
        )
        extension_dir = self.proxy_extension.directory
        if not extension_dir:
            raise ValueError("Failed to generate proxy extension")
        return extension_dir
