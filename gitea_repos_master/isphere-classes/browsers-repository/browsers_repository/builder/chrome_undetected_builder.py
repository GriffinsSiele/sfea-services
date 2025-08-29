import logging
from typing import Any, Type

import undetected_chromedriver as uc

from browsers_repository.browsers.chrome.chrome_undetected_browser import (
    ChromeUndetectedBrowser,
)
from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.builder.common_builder import CommonBuilder
from browsers_repository.interfaces import AbstractBrowser


class ChromeUndetectedBuilder(CommonBuilder):
    """Класс для настройки браузера Chrome undetected."""

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
        self._options = uc.ChromeOptions()
        self._window_size: tuple[int, int] | None = None
        self.prepared_proxy: dict | None = None
        self._headless = True

    def get_browser(self) -> Any:
        """Возвращает готовый к работе браузер.
        Для этого создает класс браузера на основе классов ChromeUndetectedBrowser
        и CommonBrowser - может быть переопределен пользователем на свой класс.
        CommonBrowser реализует методы для работы с браузерами, определенные в
        абстрактном классе AbstractBrowser.
        Для аннотации переменной рекомендуется использовать AbstractBrowser,
        если класс CommonBrowser переопределен, то использовать переопределенный класс.

        :return: ChromeUndetectedBrowser.
        """
        browser = type(
            "ChromeUndetectedBrowser_",
            (
                ChromeUndetectedBrowser,
                self.browser,
            ),
            {},
        )
        return browser(
            logger=self.logger,
            options=self._options,
            driver_executable_path=self._chromedriver,
            headless=self._headless,
            window_size=self._window_size,
            proxy_options=self.prepared_proxy,
        )
