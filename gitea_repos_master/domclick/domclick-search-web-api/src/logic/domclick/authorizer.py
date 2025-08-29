import pathlib

from putils_logic.putils import PUtils
from pydash import get, pick
from requests_logic.proxy_cache import ProxyCacheManager

from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()


class Authorizer:
    def __init__(self, auth_data, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.cookies = get(auth_data, "cookies")
        self.proxy_id = get(auth_data, "proxy_id")
        self.proxy = None
        self.prepared = False

    async def _prepare(self):
        cache_file = PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json")
        cache_manager = ProxyCacheManager(PROXY_URL, cache_file)
        cache_manager.MAX_CACHE_SIZE = 15
        self.proxy = await cache_manager.get_random(
            self.proxy_id or "-1", fallback_query={"proxygroup": "1"}
        )
        self.proxy = pick(self.proxy, "http", "https", "id")
        self.prepared = True

    async def _clean(self):
        self.prepared = False

    async def _is_ready(self):
        return self.prepared
