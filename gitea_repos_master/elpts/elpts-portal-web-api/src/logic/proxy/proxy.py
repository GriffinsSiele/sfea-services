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

proxy_cache_manager = ProxyCacheManager(
    proxy_url=settings.PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class Proxy(AbstractProxy):
    proxy_manager = proxy_cache_manager

    async def get_proxy(self) -> dict | None:
        try:
            proxy = await self.proxy_manager.get_proxy(
                query={"proxygroup": 1}, repeat=2, fallback_query={"proxygroup": 1}
            )
        except ClientConnectorError:
            raise ProxyServerConnection()
        if proxy and isinstance(proxy, dict):
            logging.info(f"Using proxy: {proxy}")
            return proxy
        return None
