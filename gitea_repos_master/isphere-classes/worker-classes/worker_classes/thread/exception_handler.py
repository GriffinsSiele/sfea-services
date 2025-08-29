import logging
from typing import Optional, Callable

from worker_classes.thread.interfaces import ExceptionHandlerDescription
from worker_classes.thread.is_exception import logger_level_by_exception

ExceptionOrNone = Optional[Exception]


class ExceptionHandler:
    def __init__(self, order_exceptions: ExceptionHandlerDescription):
        self.handle: ExceptionHandlerDescription = order_exceptions

    async def call(self, e: ExceptionOrNone, logger=logging, *args, **kwargs):
        log_function = logger_level_by_exception(e, logger)
        log_function(f"ExceptionHandler{type(e)}: {e}")

        for exception_class in self.handle:
            if (
                exception_class
                and isinstance(e, exception_class)
                or exception_class is None
                and e is None
            ):
                return await self._handler(self.handle[exception_class], *args, **kwargs)

    async def normal(self, logger=logging, *args, **kwargs):
        return await self.call(None, logger, *args, **kwargs)

    async def _handler(self, f: Callable, *args, **kwargs):
        return await f(*args, **kwargs)
