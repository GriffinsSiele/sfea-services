import logging
from copy import deepcopy

import undetected_chromedriver as uc

from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.browsers.extensions.proxy.proxy_extension import ProxyExtension
from browsers_repository.config.settings import (
    BROWSER_VERSION,
    EXPLICIT_WAIT,
    EXPLICIT_WAIT_FOR_LINK,
    IMPLICITLY_WAIT,
    MAX_PAGE_LOAD_TIMEOUT,
)


class ChromeUndetectedBrowser(CommonBrowser):
    """Undetected Chrome браузер.
    Версия браузера, которая в большинстве случаев не позволяет
    обнаружить, что браузер управляется роботом, а не человеком.
    """

    proxy_extension_cls = ProxyExtension
    EXPLICIT_WAIT = EXPLICIT_WAIT
    EXPLICIT_WAIT_FOR_LINK = EXPLICIT_WAIT_FOR_LINK
    IMPLICITLY_WAIT = IMPLICITLY_WAIT
    MAX_PAGE_LOAD_TIMEOUT = MAX_PAGE_LOAD_TIMEOUT
    CHROME_VERSION = BROWSER_VERSION

    def __init__(
        self,
        options: uc.ChromeOptions,
        driver_executable_path: str,
        headless: bool,
        window_size: tuple | None = None,
        proxy_options: dict | None = None,
        logger=logging,
    ) -> None:
        """Конструктор класса.

        :param options: Настройки браузера.
        :param driver_executable_path: Путь к chromedriver.
        :param headless: Режим headless.
        :param window_size: Размер окна браузера.
        :param proxy_options: Настройки прокси.
        :return: None.
        """
        self.driver: uc.Chrome | None = None
        self.is_started = False
        self.options = options
        self.driver_executable_path = driver_executable_path
        self.headless = headless
        self.window_size = window_size
        self.current_proxy = proxy_options
        self.proxy_extension: ProxyExtension | None = None
        self._check_chrome_version_passed()
        self.logging = logger

    def start_browser(self) -> None:
        """Запускает браузер.

        :return: None.
        """
        options = deepcopy(self.options)
        if self.current_proxy:
            self.proxy_extension = self.proxy_extension_cls()
            options.add_argument(f"--load-extension={self._load_proxy_extension()}")

        self.driver = uc.Chrome(
            version_main=self.CHROME_VERSION,
            # undetected_chromedriver не позволяет переиспользовать экземпляр ChromeOptions().
            # При попытке это сделать падает с ошибкой:
            # "RuntimeError: you cannot reuse the ChromeOptions object"
            # По этой причине используем копию опций:
            options=options,
            driver_executable_path=self.driver_executable_path,
            use_subprocess=not self.headless,
        )
        if self.window_size:
            self.driver.set_window_size(*self.window_size)
        self.driver.set_page_load_timeout(self.MAX_PAGE_LOAD_TIMEOUT)
        self.driver.implicitly_wait(self.IMPLICITLY_WAIT)
        self.is_started = True
        self.logging.info("Chrome undetected browser started")

    def close_browser(self) -> None:
        """Завершает работу браузера.

        :return: None.
        """
        if self.driver:
            self.driver.quit()
            self.driver = None
        self.proxy_extension = None
        self.is_started = False
        self.logging.info("Chrome undetected browser closed.")

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

    def _check_chrome_version_passed(self) -> None:
        """Для работы данной версии браузера важно указать версию chromedriver.
        Метод проверяет, что версия указана.

        :return: None.
        """
        if not self.CHROME_VERSION:
            raise ValueError(
                'To make Chrome undetected browser work, define the environment variable "BROWSER_VERSION".'
            )
