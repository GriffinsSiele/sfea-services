from typing import Self

from selenium.webdriver import FirefoxOptions
from seleniumwire.webdriver import FirefoxOptions as FireFixWireOptions

from browsers_repository.builder.common_builder import CommonBuilder


class CommonBuilderFireFox(CommonBuilder):
    """Общий класс для настройки браузеров FireFox."""

    _options: FirefoxOptions | FireFixWireOptions
    prepared_proxy: dict | None

    def options(self, *args, **kwargs) -> Self:
        """Не реализован для FireFox, добавлен для совместимости с Chrome.

        :return: Self.
        """
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
        if param:
            self._options.add_argument("-headless")
        return self

    def accept_languages(self, lang: str = "ru") -> Self:
        """Устанавливает заголовок "Accept-Language" для браузера.
        Значение по умолчанию "ru".

        :param lang: Значение для заголовка "Accept-Language".
        :return: Self.
        """
        self._options.set_preference("intl.accept_languages", lang)
        return self
