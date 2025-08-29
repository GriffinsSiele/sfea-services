import logging
from typing import Any, Type

from selenium import webdriver
from selenium.webdriver.chrome.service import Service

from browsers_repository.browsers.chrome.chrome_browser import ChromeBrowser
from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.builder.common_builder import CommonBuilder
from browsers_repository.interfaces import AbstractBrowser


class ChromeBuilder(CommonBuilder):
    """Общий класс для настройки браузеров Chrome."""

    def __init__(
        self,
        chromedriver: str,
        browser: Type[AbstractBrowser] = CommonBrowser,
        logger=logging,
    ) -> None:
        self.browser = browser
        self.logger = logger
        self._check_driver(chromedriver)
        self._chromedriver = chromedriver
        self._options = webdriver.ChromeOptions()
        self._service = Service(executable_path=chromedriver)
        self._window_size: tuple[int, int] | None = None
        self.prepared_proxy: dict | None = None
        self._headless = True

    def get_browser(self) -> Any:
        """Возвращает готовый к работе браузер.
        Для этого создает класс браузера на основе классов ChromeBrowser
        и CommonBrowser - может быть переопределен пользователем на свой класс.
        CommonBrowser реализует методы для работы с браузерами, определенные в
        абстрактном классе AbstractBrowser.
        Для аннотации переменной рекомендуется использовать AbstractBrowser,
        если класс CommonBrowser переопределен, то использовать переопределенный класс.

        :return: ChromeBrowser.
        """
        browser = type(
            "ChromeBrowser_",
            (
                ChromeBrowser,
                self.browser,
            ),
            {},
        )
        return browser(
            logger=self.logger,
            options=self._options,
            service=self._service,
            window_size=self._window_size,
            proxy_options=self.prepared_proxy,
        )
