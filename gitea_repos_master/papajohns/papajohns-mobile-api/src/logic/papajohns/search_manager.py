import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.worker import UnknownError
from pydash import get, pick
from urllib3.exceptions import ProxyError
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapters.response import ResponseAdapter
from src.logic.papajohns.validation import ResponseValidation
from src.logic.proxy.proxy import ProxyCacheManager, proxy_cache_manager
from src.request_params.api.search import SearchParams


class SearchPapaJohnsManager(SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.cookies = get(auth_data, "cookies")
        self.device_token = get(auth_data, "device_token")
        self.ja3_options = pick(auth_data, "ja3", "user_agent")
        self.proxy_id = get(auth_data, "proxy_id")

    async def _prepare(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            query={"id": self.proxy_id},
            repeat=3,
            adapter="simple",
            fallback_query={"proxygroup": "5"},
        )

    async def _search(self, payload, *args, **kwargs):
        payload = str(get(payload, "phone") or payload)
        try:
            sl = SearchParams(
                phone=payload,
                cookies=self.cookies,
                device_token=self.device_token,
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
            ProxyCacheManager.clear_cache()
            raise ProxyLocked(message=e)
        except Exception as e:
            logging.error(e)
            raise UnknownError(message=e)

        logging.info(f"Search for payload {payload} responded: {response}")

        response = ResponseValidation.validate_response(response)
        return ResponseAdapter.cast(response)
