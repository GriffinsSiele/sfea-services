import logging

from isphere_exceptions.session import SessionEmpty
from pydash import filter_, get
from worker_classes.logic.search_manager import SearchManager
from worker_classes.utils import short

from src.logic.doublegis.authorizer import Authorizer2GIS
from src.logic.doublegis.search_engine.search_inn_requests import SearchINNRequests2GIS
from src.logic.doublegis.search_engine.search_phone_requests import (
    SearchPhoneRequests2GIS,
)
from src.logic.misc.search_type import SearchTypes


class Search2GISManager(Authorizer2GIS, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data)

        self.search_type = None
        self.search_mapping = {
            SearchTypes.PHONE: SearchPhoneRequests2GIS,
            SearchTypes.INN: SearchINNRequests2GIS,
        }

    async def _prepare(self):
        await self._prepare_proxy()

    async def _search(self, payload, *args, **kwargs):
        if not self._auth_data:
            raise SessionEmpty()

        for key in SearchTypes:
            key_text = key.value.lower()
            if key_text in payload:
                payload = get(payload, key_text, "")
                self.search_type = key
                break

        if not self.search_type:
            self.search_type = SearchTypes.PHONE

        if not self.is_authed_session():
            logging.info("Not authed. Trying auth.")
            await self.set_auth()

        return await self.__search(payload)

    async def __search(self, payload: str):
        response = await self._search_by_class(
            self.search_mapping[self.search_type], payload
        )

        items_with_limit = len(filter_(response, lambda x: get(x, "_limit_retry_exceed")))
        items_total = len(response)

        logging.info(f"{payload} {items_with_limit}/{items_total} {short(response)}")

        return response

    async def _search_by_class(self, class_name, payload):
        sl = class_name(
            cookies=self.cookies,
            extra_query=self.auth_query,
            proxy=self.proxy,
        )
        return await sl.search(payload)
