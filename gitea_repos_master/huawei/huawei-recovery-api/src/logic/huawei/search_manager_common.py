from typing import Type

from isphere_exceptions.worker import InternalWorkerError
from worker_classes.logic.search_manager import SearchManager

from src.browser.extended_browser import ExtendedBrowser
from src.logger import logging, task_context_var
from src.logic.adapters.response_adapter import ResponseAdapter
from src.logic.handlers.input_data import InputDataHandler
from src.logic.huawei.honor_explorer import HonorExplorer
from src.logic.huawei.huawei_explorer import HuaweiExplorer


class SearchManagerCommon(SearchManager):
    site_explorer_cls: Type[HuaweiExplorer] | Type[HonorExplorer]
    input_data_handler_cls = InputDataHandler
    response_adapter_cls = ResponseAdapter

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(logger=logging, *args, **kwargs)
        self.site_explorer: HuaweiExplorer | HonorExplorer
        self.extended_browser: ExtendedBrowser | None = None
        self.is_prepared = False

    async def _prepare(self) -> None:
        if not self.is_prepared or not self.extended_browser:
            self.extended_browser = ExtendedBrowser(headless=True)
            await self.extended_browser.start_browser()
            self.site_explorer = self.site_explorer_cls(self.extended_browser)
            await self.site_explorer.prepare()
            self.is_prepared = True

    async def _clean(self) -> None:
        self.is_prepared = False
        if self.extended_browser:
            self.extended_browser.close_browser()

    async def _is_ready(self) -> bool:
        return self.is_prepared

    async def _search(
        self, payload: dict, redelivered: bool = True, *args, **kwargs
    ) -> list[dict]:
        if not self.is_prepared:
            await self._prepare()
        if not self.site_explorer:
            raise InternalWorkerError('The "site_explorer" is not prepared')
        try:
            key, data = self.input_data_handler_cls.handle_search_data(payload)
            task_context_var.set(data)

            search_result = await self.site_explorer.search(key, data)

            return self.response_adapter_cls.cast(search_result)
        finally:
            task_context_var.set("")
            await self.clean()
