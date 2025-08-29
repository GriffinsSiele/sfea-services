from typing import Type

from isphere_exceptions.proxy import ProxyServerParseError
from pydash import get, set_

from src.interfaces import AbstractProxy, AbstractSeleniumWireProxy
from src.proxy.proxy import Proxy


class SeleniumWireProxy(AbstractSeleniumWireProxy):
    """Адаптер сервиса прокси, для использования в библиотеке seleniumwire"""

    proxy: Type[AbstractProxy] = Proxy

    async def get_proxy(self, no_proxy: str | None = "localhost,127.0.0.1") -> dict:
        """
        Возвращает прокси в требуемом Seleniumwire формате
        https://pypi.org/project/selenium-wire/#proxies

        Example:

        options = {
            'proxy': {
                'http': 'http://...',
                'https': 'http://...',
                'no_proxy': 'localhost,127.0.0.1',  # excludes
                'proxy_id': '1065'
            }
        }
        """
        loaded_proxy = await self._load_proxy()
        http = get(loaded_proxy, "http")
        https = get(loaded_proxy, "https")
        proxy_id = get(loaded_proxy, "extra_fields.id")

        if not http or not https:
            raise ProxyServerParseError()

        proxy_conf = {
            "proxy": {
                "http": http,
                "https": https,
                "proxy_id": proxy_id,
            }
        }
        if no_proxy:
            set_(proxy_conf, "proxy.no_proxy", no_proxy)

        return proxy_conf

    async def _load_proxy(self) -> dict | None:
        """
        Получает прокси от прокси сервиса.

        :return: Словарь с ключами http и https
        """
        return await self.proxy().get_proxy()
