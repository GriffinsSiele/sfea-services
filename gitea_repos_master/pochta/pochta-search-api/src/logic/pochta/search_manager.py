import logging
import pathlib

from isphere_exceptions.session import SessionEmpty
from pydash import get
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.response import ResponseAdapter
from src.logic.pochta.proxy import proxy_manager
from src.logic.pochta.validation import ResponseValidation
from src.request_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class SearchPochtaManager(SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.token = get(auth_data, "token")
        self.proxy = None

    async def _prepare(self):
        self.proxy = await proxy_manager.get_proxy(
            query={"proxygroup": "1"},
            adapter="simple",
            repeat=2,
            fallback_query={"proxygroup": "1"},
        )
        logging.info(f"Using proxy {self.proxy}")

    async def _search(self, payload, *args, **kwargs):
        payload = get(payload, "phone") or payload
        if not self.token:
            raise SessionEmpty()

        return await self._search_content(payload)

    async def _search_content(self, payload):
        search_params = SearchParams(
            payload=payload,
            token=self.token,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(search_params)
        data = ResponseAdapter.cast(response)
        return data
