from proxy_manager.core import logger
from proxy_manager.core.adapter import ProxyAdapter
from proxy_manager.utils import fallback


class ProxyManager:
    DEFAULT_URL = "https://i-sphere.ru/2.00/get_proxies.php"
    DEFAULT_QUERY = {"status": "1", "order": "lasttime"}
    DEFAULT_REQUEST_CLASS = None
    DEFAULT_REQUEST_KWARGS = {"verify": False, "timeout": 8}

    def __init__(self, proxy_url, request_class, adapter=None):
        self.DEFAULT_URL = proxy_url
        self.DEFAULT_REQUEST_CLASS = request_class
        self.adapter = adapter
        self.provider = self.proxy_provider_by_request

    async def get_proxy(self, *args, query=None, **kwargs):
        query = {**(query or {}), "limit": 1}
        data = await self.get_proxies(*args, query=query, **kwargs)
        return data[0] if data and len(data) >= 1 else data

    async def get_proxies(
        self,
        proxy_id=None,
        query=None,
        proxy_group=None,
        provider=None,
        adapter=None,
        *args,
        **kwargs,
    ):
        query = self.__create_query(query, proxy_group, proxy_id)
        response = await self._get_proxy_by_provider(
            query=query, provider=provider, *args, **kwargs
        )
        return ProxyAdapter.parse(response, name=adapter or self.adapter)

    @fallback
    async def _get_proxy_by_provider(self, query=None, provider=None, *args, **kwargs):
        logger.debug(f"Proxy getting with query={query}, args={args}, kwargs={kwargs}")

        func = provider or self.provider
        data = await func(
            url=self.DEFAULT_URL,
            params={**self.DEFAULT_QUERY, **(query or {})},
            *args,
            **self.DEFAULT_REQUEST_KWARGS,
            **kwargs,
        )
        logger.debug(f"Proxy response: {data}")

        if not data:
            raise ValueError("No proxies found")
        return data

    async def proxy_provider_by_request(self, *args, **kwargs):
        logger.debug(f"Proxy getting by {self.DEFAULT_REQUEST_CLASS}")
        response = await self.DEFAULT_REQUEST_CLASS(*args, **kwargs).request()
        return response.json()

    def __create_query(self, query, proxy_group, proxy_id):
        output = {}
        if query is not None:
            output.update(query)
        if proxy_group is not None:
            output["proxygroup"] = proxy_group
        if proxy_id is not None:
            output["id"] = proxy_id

        return output
