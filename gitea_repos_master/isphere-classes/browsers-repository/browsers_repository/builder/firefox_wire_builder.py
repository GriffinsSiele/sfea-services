import logging
from typing import Any, Self, Type

from selenium.webdriver.firefox.service import Service as FireFoxService
from seleniumwire import webdriver

from browsers_repository.adapters.proxy_wire_adapter import SeleniumWireProxyAdapter
from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.browsers.firefox.firefox_wire_browser import FirefoxWireBrowser
from browsers_repository.builder.common_builder_firefox import CommonBuilderFireFox
from browsers_repository.interfaces import AbstractBrowser


class FireFoxWireBuilder(CommonBuilderFireFox):
    """Класс для настройки браузера FireFox wire."""

    proxy_adapter_wire = SeleniumWireProxyAdapter

    def __init__(
        self,
        geckodriver: str,
        browser: Type[AbstractBrowser] = CommonBrowser,
        logger=logging,
    ) -> None:
        self.browser = browser
        self.logger = logger
        self._check_driver(geckodriver)
        self._options = webdriver.FirefoxOptions()
        self._window_size: tuple[int, int] | None = None
        # прокси для всех браузеров, возвращается методом CommonBrowser.get_proxy()
        # и обеспечивает совместимость с другими версиями браузеров
        self.prepared_proxy: dict | None = None
        # прокси в формате необходимом seleniumwire
        self.prepared_proxy_wire: dict | None = None
        self._service = FireFoxService(executable_path=geckodriver)

    def proxy(self, params: dict) -> Self:
        """Устанавливает прокси для браузера.

        :param params: Параметры прокси в формате dict.
        :return: Self.

        Example:
        -------

        params = {
            "http": "http://<простая аутентификация>...",
            "https": "http://<простая аутентификация>...",
            "server": "127.0.0.1",
            "port": "5000",
            "login": "******",
            "password": "******",
            "id": "******",
            "extra_fields": {"id": "******", ...}
        }
        """
        self.prepared_proxy = self.proxy_adapter_cls().adapt(params)
        self.prepared_proxy_wire = self.proxy_adapter_wire.prepare_proxy(
            self.prepared_proxy
        )
        return self

    def get_browser(self) -> Any:
        """Возвращает готовый к работе браузер.
        Для этого создает класс браузера на основе классов FirefoxWireBrowser
        и CommonBrowser - может быть переопределен пользователем на свой класс.
        CommonBrowser реализует методы для работы с браузерами, определенные в
        абстрактном классе AbstractBrowser.
        Для аннотации переменной рекомендуется использовать AbstractBrowser,
        если класс CommonBrowser переопределен, то использовать переопределенный класс.

        :return: FirefoxWireBrowser.
        """
        browser = type(
            "FirefoxWireBrowser_",
            (
                FirefoxWireBrowser,
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
            proxy_options_wire=self.prepared_proxy_wire,
        )
