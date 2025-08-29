import asyncio
from typing import Any

import pydash
from isphere_exceptions.proxy import ProxyError
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import InternalWorkerError
from worker_classes.logic.search_manager import SearchManager

from src.logger import context_logging
from src.logic.phoneinfo import PhoneInfoRegion
from src.logic.provider import DomruProviderIDManager
from src.logic.proxy import ProxyManager
from src.request_params.interfaces import DomruRequestClient
from src.schemas import PhoneInfoDataSchema
from src.schemas.search import DomruContactDetailSchema


class SearchDomruManager(SearchManager):
    def __init__(self, auth_data, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.proxy = None

    async def _prepare(self) -> None:
        self.proxy = await ProxyManager().get_proxy()
        if not self.proxy:
            raise ProxyError()

    async def _search(self, payload, *args, **kwargs) -> list[dict[str, Any]]:
        phone_data = await PhoneInfoRegion().get_region_info(payload)
        provider_ids = DomruProviderIDManager().define_provider_ids(
            phone_data.region, phone_data.region_code, city=phone_data.city
        )
        search_payloads = await asyncio.gather(
            *[
                self.__search_by_phone(
                    provider_id=str(id), phone_data=phone_data, proxy=self.proxy
                )
                for id in provider_ids
            ]
        )
        return self.__process_results(search_payloads)

    def __process_results(self, payloads) -> list[dict[str, Any]]:
        if not payloads:
            return payloads

        if not pydash.compact(payloads):
            raise NoDataEvent()

        def is_error(p):
            return isinstance(p, tuple)

        output = []
        for payload in payloads:
            contact_data = pydash.get(payload, 0)
            if not is_error(payload) and contact_data:
                output.append(DomruContactDetailSchema(**contact_data).model_dump())

        context_logging.info(f"Payload results, {len(output)}/{len(payloads)} success.")

        if not output and payloads:
            raise InternalWorkerError(pydash.compact(payloads)[0][1])  # type: ignore[index]

        return output

    def __validate_payload(
        self, payload: dict[str, Any], provider_id: str
    ) -> list[dict[str, Any]]:
        err_msg = payload.get("message") or payload.get("code")
        if not err_msg:
            return payload.get("contacts", [])
        context_logging.info(f"Data not found (ProviderId={provider_id}): '{err_msg}'")
        return []

    async def __search_by_phone(
        self, provider_id: str, phone_data: PhoneInfoDataSchema, proxy: dict[str, Any]
    ):
        context_logging.info(
            f"Searching with region params: ProviderId:'{provider_id}'; region:'{phone_data.region}'; region_code:'{phone_data.region_code}': city:{phone_data.city}"
        )
        try:
            payload = await DomruRequestClient(
                provider_id=provider_id,
                params={
                    "contact": phone_data.phone,
                    "isActive": 0,
                },
                proxy=proxy,
            ).request()
            return self.__validate_payload(payload, provider_id)
        except Exception as exc:
            err_msg = f"{exc.message if hasattr(exc, 'message') else exc.__str__()}"
            context_logging.error(
                f"Error {type(exc).__name__} while search attempt: {err_msg}"
            )
            return (exc, err_msg)
