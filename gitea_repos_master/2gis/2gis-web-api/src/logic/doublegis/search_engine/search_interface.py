import logging

from isphere_exceptions.success import NoDataEvent
from pydash import filter_, get

from src.config.app import SearchParams
from src.logic.adapters.response import ResponseAdapter
from src.logic.doublegis.search_item_manager import SearchItemManager


class SearchTypeRequests2GISInterface:
    def __init__(self, cookies=None, extra_query=None, proxy=None):
        self.proxy = proxy
        self.cookies = cookies
        self.extra_query = extra_query

    async def search_by_item(self, response):
        output = ResponseAdapter.cast(response)
        items = filter_(output, lambda i: get(i, "_id"))

        if not items:
            raise NoDataEvent("Empty list in response")

        items_with_contacts = items[: SearchParams.MAX_ITEMS_WITH_CONTACTS]
        items_without_contacts = items[SearchParams.MAX_ITEMS_WITH_CONTACTS :]

        logging.info(f"Must create: {len(items_with_contacts)} task(s) for items")
        result_with_contacts = await SearchItemManager(
            proxy=self.proxy,
            cookies=self.cookies,
            extra_query=self.extra_query,
        ).run(items_with_contacts)

        return result_with_contacts + items_without_contacts
