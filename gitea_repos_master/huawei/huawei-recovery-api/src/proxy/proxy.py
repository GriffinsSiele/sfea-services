import pathlib

from aiohttp import ClientConnectorError
from isphere_exceptions.proxy import ProxyServerConnection
from proxy_manager import ProxyCacheManager
from putils_logic import PUtils
from requests_logic.base import RequestBaseParamsAsync
from requests_logic.proxy import ProxyManager

from src.config import settings
from src.config.settings import PROXY_URL
from src.interfaces.abstract_proxy import AbstractProxy
from src.logger.context_logger import logging

_current_file_path = pathlib.Path(__file__).parent.absolute()
_cache_file = PUtils.bp(_current_file_path, "..", "..", "proxy_cache.json")

proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=_cache_file,
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class Proxy(AbstractProxy):
    """Класс для работы с сервисом прокси"""

    proxy_manager = proxy_cache_manager

    async def get_proxy(self) -> dict | None:
        """Возвращает прокси

        :return: Прокси
        """
        try:
            proxy = await proxy_cache_manager.get_proxy(
                query={"proxygroup": "1"}, repeat=2, fallback_query={"proxygroup": "1"}
            )
        except ClientConnectorError:
            raise ProxyServerConnection()
        if proxy and isinstance(proxy, dict):
            logging.info(f"Using proxy: {proxy}")
            return proxy
        return None

    async def get_proxy_by_id(self, proxy_id: str | None = None) -> dict | None:
        """Возвращает прокси по переданному ID.
        Если указанный прокси не удалось получить, возвращает случайный прокси.

        :param proxy_id: ID прокси.
        :return: Прокси.
        """
        try:
            proxy = await self.proxy_manager(settings.PROXY_URL).get_proxy(
                query={"id": proxy_id},
                fallback_query={"proxygroup": str(settings.PROXY_GROUP)},
            )
            logging.info(f"Proxy in session ID: {proxy_id}")
        except ClientConnectorError:
            raise ProxyServerConnection()
        if proxy and isinstance(proxy, dict):
            logging.info(f"Using proxy: {proxy}")
            return proxy
        return None
