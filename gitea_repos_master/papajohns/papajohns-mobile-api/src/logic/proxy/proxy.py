import pathlib

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)
