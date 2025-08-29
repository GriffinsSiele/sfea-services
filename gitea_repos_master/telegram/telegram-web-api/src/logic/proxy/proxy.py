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


def cast_proxy(proxy):
    if not proxy:
        return None
    return {
        "proxy_type": "http",
        "addr": get(proxy, "server"),
        "port": int(get(proxy, "port")),
        "username": get(proxy, "login"),
        "password": get(proxy, "password"),
        "id": get(proxy, "extra_fields.id"),
    }
