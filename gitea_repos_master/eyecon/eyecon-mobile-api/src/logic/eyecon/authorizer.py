import logging
import pathlib

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()

proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class Authorizer:
    def __init__(self, auth_data, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.e_auth = get(auth_data, "e_auth")
        self.e_auth_c = get(auth_data, "e_auth_c")
        self.proxy = None
        self.prepared = False

    async def _prepare(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            query={"proxygroup": "2", "country": "fi"},
            fallback_query={"proxygroup": "2", "country": "fi"},
            repeat=2,
            adapter="simple",
        )
        logging.info(f"Using proxy: {self.proxy}")

        self.prepared = True

    async def _clean(self):
        self.prepared = False

    async def _is_ready(self):
        return self.prepared
