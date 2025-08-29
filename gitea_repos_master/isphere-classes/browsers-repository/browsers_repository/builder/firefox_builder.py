import logging
from typing import Any, Type

from selenium import webdriver
from selenium.webdriver.firefox.service import Service

from browsers_repository.adapters.proxy_adapter import ProxyAdapter
from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.browsers.firefox.firefox_browser import FireFoxBrowser
from browsers_repository.builder.common_builder_firefox import CommonBuilderFireFox
from browsers_repository.interfaces import AbstractBrowser


class FireFoxBuilder(CommonBuilderFireFox):
    """Класс для настройки браузера FireFox."""

    proxy_adapter_cls = ProxyAdapter

    def __init__(
        self,
        geckodriver: str,
        browser: Type[AbstractBrowser] = CommonBrowser,
        logger=logging,
    ) -> None:
        self.browser = browser
        self.logger = logger
        self._check_driver(geckodriver)
        self._chromedriver = geckodriver
        self._options = webdriver.FirefoxOptions()
        self._service = Service(executable_path=geckodriver)
        self._window_size: tuple[int, int] | None = None
        self.prepared_proxy: dict | None = None
        self._headless = True

    def get_browser(self) -> Any:
        """Возвращает готовый к работе браузер.
        Для этого создает класс браузера на основе классов FireFoxBrowser
        и CommonBrowser - может быть переопределен пользователем на свой класс.
        CommonBrowser реализует методы для работы с браузерами, определенные в
        абстрактном классе AbstractBrowser.
        Для аннотации переменной рекомендуется использовать AbstractBrowser,
        если класс CommonBrowser переопределен, то использовать переопределенный класс.

        :return: FireFoxBrowser.
        """
        browser = type(
            "ChromeUndetectedBrowser_",
            (
                FireFoxBrowser,
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
