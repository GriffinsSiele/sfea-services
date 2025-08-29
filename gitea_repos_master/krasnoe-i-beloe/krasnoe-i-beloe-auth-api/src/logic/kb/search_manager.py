import logging
import pathlib

from isphere_exceptions.session import SessionBlocked, SessionEmpty
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.response import ResponseAdapter
from src.logic.kb.authorizer import Authorizer
from src.logic.kb.validation import ResponseValidation
from src.request_params.api.search import SearchParams
from src.request_params.api.session_get import SessionGetParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class SearchKBManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data=auth_data, *args, **kwargs)
        self.retry_activate_blocked = False

    async def _search(self, payload, *args, **kwargs):
        payload = str(payload.get("phone", "") if isinstance(payload, dict) else payload)

        if not self.cookies:
            raise SessionEmpty()

        try:
            return await self._search_content(payload)
        except SessionBlocked as e:
            if self.retry_activate_blocked:
                raise e
            logging.warning("Session blocked. Retrying to activate session")
            self.retry_activate_blocked = True
            await self._activate_session()
            return await self._search(payload, *args, **kwargs)

    async def _search_content(self, payload):
        search_params = SearchParams(
            phone=payload,
            cookies=self.cookies,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(search_params)
        data = ResponseAdapter.cast(response)
        self.cookies = dict(response.cookies)
        return data

    async def _activate_session(self):
        search_params = SessionGetParams(
            cookies=self.cookies,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(search_params)
        self.cookies = dict(response.cookies)
        logging.info(f"Session activated, new cookies: {response.cookies}")

    def get_session(self):
        return {"cookies": self.cookies or {}}
