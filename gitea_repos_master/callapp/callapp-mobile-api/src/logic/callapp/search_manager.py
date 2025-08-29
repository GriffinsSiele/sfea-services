from isphere_exceptions.session import SessionEmpty
from pydash import get
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapters.response import ResponseAdapter
from src.logic.callapp.validation import ResponseValidation
from src.request_params.api.search import SearchParams


class SearchCallAppManager(SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.token = get(auth_data, "token")
        self.user_id = get(auth_data, "user_id")

    async def _search(self, payload, *args, **kwargs):
        payload = str(payload.get("phone", "") if isinstance(payload, dict) else payload)
        if not self.token:
            raise SessionEmpty()
        sp = SearchParams()
        sp.set_query(payload, self.user_id, self.token)
        response = await ResponseValidation.validate_response(sp)
        return ResponseAdapter.cast(response)
