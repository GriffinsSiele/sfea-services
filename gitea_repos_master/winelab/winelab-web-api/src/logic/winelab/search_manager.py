import logging
import pathlib

from isphere_exceptions.session import SessionBlocked, SessionEmpty
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.response import ResponseAdapter
from src.logic.winelab.authorizer import Authorizer
from src.logic.winelab.validation import ResponseValidation
from src.request_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class SearchWinelabManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data=auth_data, *args, **kwargs)
        self.retry_activate_blocked = False

    async def _search(self, payload, redelivered=False, *args, **kwargs):
        payload = str(
            payload.get("phone", "") or payload.get("email", "")
            if isinstance(payload, dict)
            else payload
        )

        if not self.login or not self.password:
            raise SessionEmpty()

        try:
            return await self._search_content(payload)
        except SessionBlocked as e:
            if self.retry_activate_blocked:
                raise e
            logging.warning("Session blocked. Retrying to activate session")
            self.retry_activate_blocked = True
            await self._update_session()
            return await self._search(payload, *args, **kwargs)

    async def _search_content(self, payload):
        search_params = SearchParams(
            payload=payload,
            cookies=self.cookies,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(
            search_params, format="json"
        )
        return ResponseAdapter.cast(response)
