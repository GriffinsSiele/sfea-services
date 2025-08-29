import pathlib
from asyncio.exceptions import TimeoutError as TE

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.worker import UnknownError
from putils_logic.putils import PUtils
from requests_logic.proxy_cache import ProxyCacheManager
from urllib3.exceptions import ProxyError

from src.config.settings import PROXY_URL
from src.request_params.interfaces.base import RequestParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class ResponseValidation:
    @staticmethod
    async def validate_response(rp: RequestParams):
        cache_file = PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json")
        cache_manager = ProxyCacheManager(PROXY_URL, cache_file)

        try:
            response = await rp.request()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            TE,
            ConnectionError,
        ) as e:
            cache_manager.clear_cache()
            raise ProxyBlocked(str(e))
        except Exception as e:
            raise UnknownError(e)

        if "fingerprintjs2" in response.text:
            raise SessionBlocked("Обнаружена блокировка сессии")

        return response
