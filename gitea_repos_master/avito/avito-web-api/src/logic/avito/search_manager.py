import logging
import pathlib
import random

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions import TimeoutException
from isphere_exceptions.proxy import ProxyError
from isphere_exceptions.proxy import ProxyError as PE
from isphere_exceptions.worker import UnknownError
from pydash import get, pick
from requests_logic.proxy import ProxyManager
from worker_classes.logic.search_manager import SearchManager

from src.config.settings import PROXY_URL
from src.logic.adapters.response import AvitoProxyBlocked, ResponseAdapter
from src.logic.avito.proxy import proxy_cache_manager
from src.logic.avito.validation import ResponseValidation
from src.requet_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


def distribution_proxy():
    return str(random.choice([1, 1, 1, 5, 5, 5, 5, 5, 5, 5]))


class SearchAvitoManager(SearchManager):

    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__()
        self.cookies = get(auth_data, "cookies")
        self.device = get(auth_data, "device")

        self.proxy = None

    async def _prepare(self):
        p1, p2 = distribution_proxy(), distribution_proxy()
        self.proxy = await proxy_cache_manager.get_proxy(
            {"proxygroup": p1},
            fallback_query={"proxygroup": p2},
            repeat=3,
            adapter="simple",
        )
        logging.info(f"Using proxy: {self.proxy}")

    async def _search(self, payload, *args, **kwargs):
        payload = str(
            payload.get("phone", "") or payload.get("email", "")
            if isinstance(payload, dict)
            else payload
        )
        try:
            sl = SearchParams(
                login=payload,
                cookies=self.cookies,
                device=self.device,
                proxy=self.proxy,
            )
            response = await sl.request()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            ConnectionError,
        ) as e:
            raise AvitoProxyBlocked(e)
        except Exception as e:
            if "Failed to perform" in str(e):
                raise PE(f"Ошибка подключения: {e}")
            if "timed" in str(e) or "timeout" in str(e):
                raise TimeoutException(f"Timeout: {e}")
            raise UnknownError(e)

        logging.info(f"Search for payload {payload} responded: {response.status_code}")
        response = ResponseValidation.validate_response(response)
        return ResponseAdapter.cast(response)

    async def _clean(self):
        self.cookies = None
        self.device = None
        self.proxy = None
        self.logging = None
