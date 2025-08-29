from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.worker import InternalWorkerTimeout
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import TimeoutHandler

from src.config import ConfigApp
from src.fastapi.schemas import AppleSearchData
from src.logger.context_logger import logging
from src.logic.apple import Apple
from src.logic.apple.exception_handler import exception_handler, exception_wrapper
from src.utils import now


class AppleSearchManager(SearchManager):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.apple = Apple()

    async def _prepare(self) -> None:
        await self.apple.prepare()

    @exception_wrapper
    async def _search(self, data: AppleSearchData, *args, **kwargs) -> dict:
        if not data.payload:
            raise SourceIncorrectDataDetected()

        if data.timeout and data.starttime and data.timeout + data.starttime < now():
            raise InternalWorkerTimeout()

        handler = TimeoutHandler(timeout=ConfigApp.TASK_TIMEOUT)
        search_result = await handler.execute(
            self.apple.search, data.payload, *args, **kwargs
        )
        await self._clean()
        return await exception_handler.normal(
            logger=logging,
            search_data=data,
            search_result=search_result,
        )

    async def _clean(self) -> None:
        self.apple.clean()
