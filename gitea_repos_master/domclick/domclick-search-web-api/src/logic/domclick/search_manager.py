import pathlib

from isphere_exceptions.session import SessionEmpty
from isphere_exceptions.source import SourceIncorrectDataDetected
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.response import ResponseAdapter
from src.logic.domclick.authorizer import Authorizer
from src.logic.domclick.validation import ResponseValidation
from src.request_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class SearchDomclickManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data=auth_data, *args, **kwargs)

    async def _search(self, payload, redelivered=False, *args, **kwargs):
        if redelivered:
            raise SourceIncorrectDataDetected()

        if not self.cookies:
            raise SessionEmpty()

        if not self.prepared:
            await self._prepare()

        response = await self._search_content(payload)
        return ResponseAdapter.cast(response)

    async def _search_content(self, payload):
        search_params = SearchParams(
            phone=payload,
            cookies=self.cookies,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(search_params)
        return response
