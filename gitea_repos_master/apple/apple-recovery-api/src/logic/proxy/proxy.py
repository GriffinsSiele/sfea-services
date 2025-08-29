import pathlib

from aiohttp import ClientConnectorError
from isphere_exceptions.proxy import ProxyServerConnection
from proxy_manager import ProxyCacheManager
from putils_logic import PUtils
from requests_logic.base import RequestBaseParamsAsync

from src.config import settings
from src.interfaces import AbstractProxy
from src.logger.context_logger import logging

_current_file_path = pathlib.Path(__file__).parent.absolute()


class Proxy(AbstractProxy):
    """
    Класс для работы с сервисом прокси.

    Example:
    -------
    ``get_proxy() -> '{'http': 'http://...', 'https': 'http://...', 'server': '<IP>', 'port': '<port>',
    'login': '****', 'password': '****', 'extra_fields': {'id': '****', 'name': '<IP>', 'server': '<IP>',
    'port': '<poort>', 'login': '****', 'password': '****', 'country': 'ru', 'proxygroup': '1', 'status': '1',
    'starttime': '2020-10-22 09:39:18', 'lasttime': '2024-05-16 17:22:31',
    'successtime': '2024-05-16 17:22:32', 'endtime': None}}'``
    """

    proxy_cache_manager = ProxyCacheManager(
        proxy_url=settings.PROXY_URL,
        cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
        request_class=RequestBaseParamsAsync,
        rate_update=50,
    )

    async def get_proxy(self) -> dict | None:
        """Возвращает прокси.

        :return: Словарь с ключами 'http' и 'https'.
        """
        try:
            proxy = await self.proxy_cache_manager.get_proxy(
                query={"proxygroup": "1"}, fallback_query={"proxygroup": "1"}
            )
        except ClientConnectorError:
            raise ProxyServerConnection()
        if proxy and isinstance(proxy, dict):
            logging.info(f"Using proxy: {proxy}")
            return proxy
        return None
