import asyncio
import logging
from asyncio import sleep

from isphere_exceptions.proxy import ProxyBlocked, ProxyLocked
from isphere_exceptions.session import SessionLimitError
from isphere_exceptions.worker import UnknownError
from pydash import get

from src.config.app import SearchParams
from src.logic.adapters.response import ResponseAdapter
from src.logic.adapters.viewpoints import ViewpointManager
from src.logic.doublegis.validation import ResponseValidation
from src.request_params.api.search_item import SearchItem
from src.utils.utils import random_float


class SearchItemManager:
    MAX_RETRIES = SearchParams.MAX_RETRIES_IN_SEARCH_ITEM

    def __init__(self, proxy=None, cookies=None, extra_query=None):
        if extra_query is None:
            extra_query = {}
        self.retry_counter = {}
        self.proxy = proxy
        self.cookies = cookies
        self.extra_query = extra_query

    async def run(self, tasks):
        return await asyncio.gather(*(self._search_with_retry(t) for t in tasks))

    async def _search_with_retry(self, item):
        count = self.increase_counter(item)

        if count >= SearchItemManager.MAX_RETRIES:
            logging.error("Limit error")
            return {"_limit_retry_exceed": True, **item}

        try:
            return await self.search_by_id(item)
        except (SessionLimitError, ProxyBlocked, ProxyLocked) as e:
            logging.error(f'Error during handling task {get(item, "_id")}: {e}')
            await sleep(random_float(0.1, 2))
            return await self._search_with_retry(item)

    async def search_by_id(self, item):
        viewpoint1, viewpoint2 = ViewpointManager.coordinates_to_rect(
            get(item, "coordinates", [])
        )

        si = SearchItem(proxy=self.proxy)
        si.set_query(
            get(item, "_id"), viewpoint1, viewpoint2, extra_query=self.extra_query
        )
        si.cookies = self.cookies

        try:
            response = await si.request()
        except (ConnectionError, asyncio.TimeoutError) as e:
            raise ProxyBlocked(e)
        except Exception as e:
            raise UnknownError(e)

        response = ResponseValidation.validate_response(response)
        output = ResponseAdapter.cast(response)

        return {**item, **get(output, "0", {}), "_limit_retry_exceed": False}

    def increase_counter(self, item):
        item_id = get(item, "_id")
        count = get(self.retry_counter, item_id, 0) + 1
        self.retry_counter[item_id] = count
        logging.info(f"Task run {count} time with [id={item_id}]")
        return count
