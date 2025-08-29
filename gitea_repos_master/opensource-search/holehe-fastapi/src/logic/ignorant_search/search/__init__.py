import asyncio

import phonenumbers
from worker_classes.logic.search_manager import SearchManager

from src.fastapi.schemas import SearchPayload
from src.logic.ignorant_search.modules.client import Ignorant
from src.request_params.interfaces.base import Client
from src.utils import e_to_dict


class SearchIgnorantManager(SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)

    async def _search(self, payload: SearchPayload, *args, **kwargs):
        modules = payload.payload_raw.get("modules")
        phone = payload.payload

        phone_parse = phonenumbers.parse(
            phone if phone.startswith("+") else "+" + phone, None
        )
        country_code = str(phone_parse.country_code)
        phone_no_country = str(phone_parse.national_number)

        responses = await asyncio.gather(
            *[
                self.__search_one(module, phone_no_country, country_code)
                for module in modules
            ]
        )
        output = {m: r for m, r in zip(modules, responses)}
        return output

    async def __search_one(self, module, phone, country_code):
        try:
            func, client_args, adapter = Ignorant().get(module)
            output = []
            await func(phone, country_code, Client(**client_args), output)
            return adapter(output)
        except Exception as e:
            self.logging.warning(e)
            return e_to_dict(e)
