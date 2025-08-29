from isphere_exceptions.session import SessionBlocked, SessionEmpty
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.payload import PayloadType
from src.logic.adapter.response import ResponseAdapter
from src.logic.petrovich.authorizer import Authorizer
from src.logic.petrovich.validation import ResponseValidation
from src.request_params.api.search import SearchParams


class SearchPetrovichManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data=auth_data, *args, **kwargs)

    async def _search(self, payload, *args, **kwargs):

        payload, payload_type = PayloadType.parse(payload)

        if not self.cookies:
            raise SessionEmpty()

        return await self._search_content(payload, payload_type)

    async def _search_content(self, payload, payload_type):
        search_params = SearchParams(
            payload=payload,
            payload_type=payload_type,
            cookies=self.cookies,
            proxy=self.proxy,
        )
        try:
            response = await ResponseValidation.validate_response(search_params)
        except SessionBlocked:
            await self.create_new_session()
            return await self._search_content(payload, payload_type)

        data = ResponseAdapter.cast(response)
        return data
