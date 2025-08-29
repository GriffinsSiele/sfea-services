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
        self.cookies = get(auth_data, "cookies")
        self.proxy = None

    async def _prepare_proxy(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            {"proxygroup": "1"},
            fallback_query={"proxygroup": "1"},
            repeat=3,
            adapter="simple",
        )
        logging.info(f"Using proxy: {self.proxy}")
