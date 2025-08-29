from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.worker import InternalWorkerTimeout
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import TimeoutHandler

from src.config import ConfigApp
from src.fastapi.schemas import XiaomiSearchData
from src.logger.context_logger import logging
from src.logic.xiaomi.exception_handler import exception_handler
from src.logic.xiaomi.xiaomi import Xiaomi
from src.utils import now


class XiaomiSearchManager(SearchManager):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._SearchManager__is_prepared_stated = True
        self.xiaomi = Xiaomi()

    async def _prepare(self) -> None:
        await self.xiaomi.prepare()

    async def _search(self, data: XiaomiSearchData, *args, **kwargs) -> dict:
        search_result = {}
        try:
            if not data.payload:
                raise SourceIncorrectDataDetected()

            if data.timeout and data.starttime and data.timeout + data.starttime < now():
                raise InternalWorkerTimeout()

            handler = TimeoutHandler(timeout=ConfigApp.TASK_TIMEOUT)
            search_result = await handler.execute(
                self.xiaomi.search, data.payload, *args, **kwargs
            )
        except Exception as e:
            return await exception_handler.call(e, logger=logging)
        else:
            return await exception_handler.normal(
                logger=logging,
                search_result=search_result,
            )
        finally:
            await self._clean()

    async def _clean(self) -> None:
        self.xiaomi.clean()
