import logging
import time
from typing import Any

from worker_classes.utils import short_str


class SearchManager:
    def __init__(self, logger=logging, *args, **kwargs):
        self.logging = logger

    async def search(self, payload: Any) -> Any:
        start_time = time.time()
        self.logging.info(f'Start search for payload: "{payload}"')

        try:
            result = await self._search(payload)
            self.logging.info(f'Found: "{short_str(result)}"')
        except Exception as e:
            raise e
        finally:
            end_time = time.time()
            self.logging.info(f"API elapsed time: {round(end_time - start_time, 2)} sec")
        return result

    async def _search(self, payload: Any):
        raise NotImplementedError()
