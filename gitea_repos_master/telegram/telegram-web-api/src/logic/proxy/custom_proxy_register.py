from pydash import map_
from requests_logic.proxy import ProxyManager


class MyProxyManager(ProxyManager):
    query = {"status": "1"}

    async def get_proxies(self, query=None, proxy_group=None, fallback_query=None):
        try:
            response = await self._get_proxy(query, proxy_group)
            return map_(response.json(), ProxyManager._adapter_one)
        except ValueError:
            if fallback_query:
                return await self._resolve_not_found(fallback_query)
