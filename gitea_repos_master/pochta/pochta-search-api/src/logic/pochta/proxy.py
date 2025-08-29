import pathlib

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_manager = ProxyCacheManager(
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    proxy_url=PROXY_URL,
    request_class=RequestBaseParamsAsync,
    adapter="simple",
    rate_update=50,
)
