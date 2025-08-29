import asyncio
import logging
from typing import Union

from isphere_exceptions.session import SessionEmpty
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapters.profile import ProfileAdapter
from src.logic.numbuster.authorizer import Authorizer
from src.logic.numbuster.concurrent_tasks_generator import ConcurrentTasksGenerator
from src.logic.validation import ResponseValidation
from src.request_params.api.v202203.incoming import IncomingParams
from src.request_params.api.v202204.search import SearchParams
from src.utils.utils import timing


class SearchNumbusterManagerInterface(Authorizer, SearchManager):
    def __init__(self, auth_data=None, proxy=None, logger=logging, *args, **kwargs):
        super().__init__(auth_data, proxy, logger=logger, *args, **kwargs)
        self.logging = logger

    async def _search(self, payload: Union[dict, str], *args, **kwargs):
        payload = str(payload.get("phone", "") if isinstance(payload, dict) else payload)
        responses = await self._main_process(self._incoming_call, payload)
        response = self._parse_responses(responses)
        self._validate_profile(response)
        return response

    async def _main_process(self, func, payload):
        garbage_tasks = ConcurrentTasksGenerator().create(self)
        self.logging.info(f"Parallel tasks: {garbage_tasks}")

        call_methods = [
            func(payload),  # Search request
            *[
                self._suppress_exception_call(e) for e in garbage_tasks
            ],  # Garbage requests
        ]
        responses = await asyncio.gather(*call_methods, return_exceptions=True)
        return responses

    async def _prepare(self):
        if not self.access_token:
            raise SessionEmpty()

        await self._prepare_host_proxy()

    async def _search_method(self, payload, request_params):
        return await ResponseValidation.validate_request(
            request_params(
                phone_number=payload,
                access_token=self.access_token,
                domain=self.host,
                proxy=self.proxy,
            )
        )

    async def _incoming_call(self, payload):
        return await self._search_method(payload, IncomingParams)

    async def _search_call(self, payload):
        return await self._search_method(payload, SearchParams)

    def _parse_responses(self, responses):
        if not responses:
            raise UnknownError("Empty responses")

        search_response = responses[0]
        if isinstance(search_response, Exception):
            raise search_response
        self.logging.info(f"Response: {search_response}")

        for response in responses[1:]:
            if isinstance(response, Exception):
                self.logging.error(f"Invalid garbage response: {response}")

        return ProfileAdapter.cast(search_response)

    def _validate_profile(self, response):
        first_name, last_name, name = (
            get(response, "0.first_name"),
            get(response, "0.last_name"),
            get(response, "0.name"),
        )
        if not first_name and not last_name and not name:
            raise NoDataEvent()

    @timing
    async def _suppress_exception_call(self, instance_call):
        try:
            return await ResponseValidation.validate_request(instance_call)
        except Exception as e:
            logging.warning(e)
            return None

    @property
    def next_use(self):
        return None
