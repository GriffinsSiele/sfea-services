import asyncio
import logging

from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.worker import UnknownError

from src.logic.doublegis.search_engine.search_interface import (
    SearchTypeRequests2GISInterface,
)
from src.logic.doublegis.validation import ResponseValidation
from src.request_params.api.search_inn import SearchINN


class SearchINNRequests2GIS(SearchTypeRequests2GISInterface):
    def __init__(self, cookies=None, extra_query=None, proxy=None):
        super().__init__(cookies, extra_query, proxy)

    async def search(self, inn):
        si = SearchINN(proxy=self.proxy)
        si.set_query(inn, extra_query=self.extra_query)
        si.cookies = self.cookies

        try:
            response = await si.request()
        except (ConnectionError, asyncio.TimeoutError) as e:
            raise ProxyBlocked(e)
        except Exception as e:
            raise UnknownError(e)

        logging.info(
            f"Search for inn {inn} responded: {response}. {response.text[:300]}..."
        )
        response = ResponseValidation.validate_response(response)

        return await self.search_by_item(response)
