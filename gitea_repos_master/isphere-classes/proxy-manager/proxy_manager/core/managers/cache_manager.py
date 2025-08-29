import random

from proxy_manager.core import logger
from proxy_manager.core.file_manager import FileManager
from proxy_manager.core.managers.request_manager import ProxyManager
from proxy_manager.utils import Singleton


class ProxyCacheManager(FileManager, ProxyManager, metaclass=Singleton):
    def __init__(self, rate_update=10, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.count_usage = 0
        self.rate_update = rate_update
        self.provider = self.proxy_provider_by_cache

    async def get_proxy(self, *args, query=None, **kwargs):
        data = await self.get_proxies(*args, query=query, **kwargs)
        return random.choice(data) if data and len(data) >= 1 else data

    async def init_cache(self):
        logger.debug("Proxy cache initialization started")
        data = await super().get_proxies(
            provider=self.proxy_provider_by_request, adapter="raw", repeat=1
        )
        await self._write_cache(data)
        logger.debug("Proxy cache initialization finished")

    async def proxy_provider_by_cache(self, params=None, *args, **kwargs):
        logger.debug("Proxy getting by cache")

        if self.count_usage % self.rate_update == 0:
            logger.debug("Proxy requires cache update")
            try:
                await self.init_cache()
            except Exception as e:
                logger.warning(f"Failure in updating proxy cache: {e}")

        self.count_usage += 1

        data = self._read_cache()
        if not data:
            logger.warning("Proxy cache is empty")
            self.count_usage = 0  # Зануление для обновления
            return await self.proxy_provider_by_request(*args, params=params, **kwargs)
        return self.find_by_query(data, params)

    def find_by_query(self, data, query):
        ignore_fields = ["limit", "order"]
        output = []
        for proxy in data:
            is_match = True
            for field, value in (query or {}).items():
                if field in ignore_fields:
                    continue
                if str(proxy.get(field)) != str(value):
                    is_match = False
                    break
            if is_match:
                output.append(proxy)
        return output
