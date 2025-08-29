import asyncio

from worker_classes.logic.search_manager import SearchManager

from src.fastapi.schemas import SearchEmailPayload
from src.logic.holehe_search.modules.client import Holehe
from src.request_params.interfaces.base import Client
from src.utils import e_to_dict


class SearchHoleheManager(SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)

    async def _search(self, payload: SearchEmailPayload, *args, **kwargs):
        modules = payload.payload_raw.get("modules") or []

        responses = await asyncio.gather(
            *[self.__search_one(module, payload.payload) for module in modules]
        )
        output = {m: r for m, r in zip(modules, responses)}
        return output

    async def __search_one(self, module, email):
        try:
            func, client_args, adapter = Holehe().get(module)
            output = []
            await func(email, Client(**client_args), output)
            return adapter(output)
        except Exception as e:
            self.logging.warning(e)
            return e_to_dict(e)
