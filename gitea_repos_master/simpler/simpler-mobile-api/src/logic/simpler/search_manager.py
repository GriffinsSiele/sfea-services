import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.worker import UnknownError
from pydash import get
from urllib3.exceptions import ProxyError
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapters.response import ResponseAdapter
from src.logic.proxy.proxy import ProxyCacheManager
from src.logic.simpler.validation import ResponseValidation
from src.request_params.api.search import SearchParams


class SearchSimplerManager(SearchManager):
    def __init__(self, auth_data=None, proxy=True, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.token = get(auth_data, "token")
        self.use_proxy = proxy

    async def _prepare(self):
        self.proxy = None

    async def _search(self, payload, *args, **kwargs):
        payload = str(get(payload, "phone") or payload)
        try:
            sl = SearchParams(
                token=self.token,
                phones=payload,
                proxy=self.proxy,
            )
            response = await sl.request()
        except (
            ProxyError,
            ConnectionError,
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
        ) as e:
            logging.error(e)
            raise ProxyBlocked(e)
        except Exception as e:
            logging.error(e)
            raise UnknownError(e)

        logging.info(f"Response: {response}")

        response = ResponseValidation.validate_response(response)
        return ResponseAdapter.cast(response)
