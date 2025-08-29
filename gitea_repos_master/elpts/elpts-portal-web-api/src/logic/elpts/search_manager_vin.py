from isphere_exceptions.source import SourceIncorrectDataDetected
from worker_classes.logic.search_manager import SearchManager

from src.logic.elpts.elpts_vin import ElPtsVin
from src.logic.elpts.exception_handler import exception_handler, exception_wrapper


class ElPtsSearchManagerVin(SearchManager):
    elpts_cls = ElPtsVin

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.elpts = self.elpts_cls()

    async def _prepare(self) -> None:
        await self.elpts.prepare()

    @exception_wrapper
    async def _search(self, data: str | None, *args, **kwargs) -> dict:
        if not data:
            raise SourceIncorrectDataDetected()
        search_result = await self.elpts.search(data)
        return await exception_handler.normal(
            search_result=search_result,
        )

    async def _clean(self) -> None:
        self.elpts.clean()
