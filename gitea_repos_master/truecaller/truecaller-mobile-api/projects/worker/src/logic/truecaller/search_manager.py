import asyncio
import logging
import pathlib
from typing import Any

from isphere_exceptions.session import SessionEmpty
from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get
from requests_logic.base import RequestBaseParamsAsync
from worker_classes.logic.search_manager import SearchManager

from lib.src.logic.device.generator import DeviceGenerator
from lib.src.request_params.api.credentials import CredentialsParams
from src.config.settings import PROXY_URL
from src.logic.adapter.bulk import BulkAdapter
from src.logic.adapter.profile import ProfileAdapter
from src.logic.validation import ResponseValidation
from src.request_params.api.bulk import BulkParams
from src.request_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class SearchTruecallerManager(SearchManager):
    def __init__(self, auth_data=None, logger=logging, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.token = get(auth_data, "token")

        self.proxy_id = get(auth_data, "proxy_id")
        self.proxy = self.proxy_id

        self.search_type = get(auth_data, "type", "bulk")
        self.device = DeviceGenerator.from_payload(
            get(auth_data, "device"), seed=self.token
        )
        self.phone = get(auth_data, "phone")

        self.logging = logger

    async def _prepare(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            query={"id": self.proxy or "-1"}, fallback_query={"proxygroup": "1"}, repeat=3
        )

    async def _search(self, payload: Any, *args, **kwargs):
        payload = str(payload.get("phone", "") if isinstance(payload, dict) else payload)
        if not self.token:
            raise SessionEmpty()

        responses = await asyncio.gather(
            *[self.garbage_task(), self._search_process(payload)]
        )
        return get(responses, "1", [])

    def __search_class(self):
        if self.search_type == "bulk":
            return BulkParams, BulkAdapter.cast

        return SearchParams, lambda x: x

    async def garbage_task(self):
        try:
            await CredentialsParams(
                installation_id=self.token,
                device=self.device,
                proxy=self.proxy,
            ).request()
            return "ok"
        except Exception as e:
            logging.warning(e)
            return "error"

    async def _search_process(self, phone_number):
        search_class, adapter = self.__search_class()
        self.logging.info(f"Using search class: {search_class}")

        sp = search_class(
            phone_number=phone_number,
            installation_id=self.token,
            device=self.device,
            proxy=self.proxy,
        )
        try:
            response = await ResponseValidation.validate_request(sp)
        except Exception as e:
            raise e

        self.logging.info(f"Response: {response}")
        return ProfileAdapter.cast(adapter(response))

    def get_session(self):
        return {
            "token": self.token,
            "type": self.search_type,
            "proxy_id": get(self.proxy, "extra_fields.id", "-1"),
            "device": self.device.to_dict(),
            "phone": self.phone,
        }

    @property
    def next_use(self):
        return {"seconds": 1}
