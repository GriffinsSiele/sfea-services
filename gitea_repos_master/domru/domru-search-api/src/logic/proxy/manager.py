import os

from aiohttp import ClientConnectorError
from isphere_exceptions.proxy import ProxyServerConnection
from proxy_manager import ProxyCacheManager
from putils_logic import PUtils
from requests_logic.base import RequestBaseParamsAsync

from src.config import settings
from src.logger import context_logging

proxy_cache = ProxyCacheManager(
    proxy_url=settings.PROXY_URL,
    cache_file=PUtils.bp(
        os.path.abspath(__file__), "..", "..", "..", "..", "proxy_cache.json"
    ),
    request_class=RequestBaseParamsAsync,
    rate_update=settings.PROXY_RATE_UPDATE,
)


class ProxyManager:

    async def get_proxy(self) -> dict:
        try:
            proxy = await proxy_cache.get_proxy(
                query={"proxygroup": "1"},
                fallback_query={"proxygroup": "1"},
                repeat=2,
            )
        except ClientConnectorError:
            raise ProxyServerConnection()
        if proxy and isinstance(proxy, dict):
            context_logging.info(f"Using proxy: {proxy}")
            return proxy
        return {}
