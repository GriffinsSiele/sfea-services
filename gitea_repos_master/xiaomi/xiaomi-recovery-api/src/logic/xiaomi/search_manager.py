from isphere_exceptions.worker import InternalWorkerError
from worker_classes.logic.search_manager import SearchManager

from src.browser.browser import Browser
from src.browser.browser_options import BrowserOptions
from src.browser.xiaomi_browser import XiaomiBrowser
from src.interfaces.abstract_browser import AbstractBrowser
from src.logger import logging, task_context_var
from src.logic.adapters.response_adapter import ResponseAdapter
from src.logic.handlers.input_data import InputDataHandler
from src.logic.xiaomi.xiaomi_explorer import XiaomiExplorer


class SearchXiaomiManager(SearchManager):
    xiaomi_explorer_cls = XiaomiExplorer
    response_adapter_cls = ResponseAdapter
    input_data_handler_cls = InputDataHandler

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(logger=logging, *args, **kwargs)
        self.xiaomi_explorer: XiaomiExplorer | None
        self.chrome_browser: AbstractBrowser | None = None
        self.is_prepared = False

    async def _prepare(self) -> None:
        if not self.is_prepared or not self.chrome_browser:
            browser_options = (
                await BrowserOptions()
                .load_from_file()
                .set_random_screen_size()
                .set_proxy()
            )

            self.chrome_browser = Browser.configure(
                options=browser_options.get_options(), browser=XiaomiBrowser()
            )
            self.chrome_browser.start_browser()

            self.xiaomi_explorer = self.xiaomi_explorer_cls(self.chrome_browser)
            await self.xiaomi_explorer.prepare()

            self.is_prepared = True

    async def _clean(self) -> None:
        self.is_prepared = False
        if self.chrome_browser:
            self.chrome_browser.close_browser()

    async def _is_ready(self) -> bool:
        return self.is_prepared

    async def _search(
        self, payload: dict | str, redelivered: bool = True, *args, **kwargs
    ) -> list[dict]:
        if not self.is_prepared:
            await self._prepare()
        if not self.xiaomi_explorer:
            raise InternalWorkerError('The "xiaomi_explorer" is not prepared')
        try:
            explorer, payload = self.input_data_handler_cls.handle_search_data(payload)
            task_context_var.set(payload)

            xiaomi_explorer = None
            if explorer == "phone":
                xiaomi_explorer = self.xiaomi_explorer.search_phone
            if explorer == "email":
                xiaomi_explorer = self.xiaomi_explorer.search_email
            if not xiaomi_explorer:
                raise InternalWorkerError("The xiaomi_explorer not defined")

            search_result = await xiaomi_explorer(payload, explorer)
            return self.response_adapter_cls.cast(search_result)
        finally:
            task_context_var.set("")
            await self.clean()
