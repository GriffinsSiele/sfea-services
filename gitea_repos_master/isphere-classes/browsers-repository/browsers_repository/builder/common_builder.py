from pathlib import Path
from typing import Self

from selenium.webdriver import ChromeOptions, FirefoxOptions
from seleniumwire.webdriver import FirefoxOptions as FireFixWireOptions
from undetected_chromedriver import ChromeOptions as ChromeUndetectedOptions

from browsers_repository.adapters.proxy_adapter import ProxyAdapter


class CommonBuilder:
    """Общий класс для настройки браузеров."""

    proxy_adapter_cls = ProxyAdapter
    _options: (
        ChromeOptions | FirefoxOptions | ChromeUndetectedOptions | FireFixWireOptions
    )
    prepared_proxy: dict | None
    _window_size: tuple[int, int] | None
    _headless: bool

    def options(
        self,
        list_options: tuple = (
            "--no-sandbox",
            "--disable-blink-features=AutomationControlled",
        ),
    ) -> Self:
        for option in list_options:
            self._options.add_argument(option)
        return self

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
        return self

    def headless(self, param: bool = True) -> Self:
        """Устанавливает режим headless.

        :param param: Значение True - режим headless включен. False - выключен.
        :return: Self.
        """
        self._options.headless = param
        self._headless = param
        return self

    def accept_languages(self, lang: str = "ru-RU") -> Self:
        """Устанавливает заголовок "Accept-Language" для браузера.
        Значение по умолчанию "ru-RU".

        :param lang: Значение для заголовка "Accept-Language".
        :return: Self.
        """
        self._options.add_argument(
            f"--lang={lang}",
        )
        self._options.add_argument(f"--accept-lang={lang}")
        return self

    def window_size(self, width: int, height: int) -> Self:
        """Устанавливает размер окна браузера.

        :param width: Ширина окна браузера.
        :param height: Высота окна браузера.
        :return: Self.
        """
        self._window_size = (width, height)
        return self

    @staticmethod
    def _check_driver(driver: str) -> None:
        if Path(driver).exists() and Path(driver).is_file():
            return None
        raise ValueError(f"File not exist: {driver}")
