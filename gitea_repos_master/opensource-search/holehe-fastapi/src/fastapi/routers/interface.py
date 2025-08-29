from typing import Dict, List

from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import timing

from src.fastapi.schemas import SearchPayload, SearchResponse
from src.logger.context_logger import logging
from src.logger.logger_adapter import request_id_contextvar


@timing("Total processing task time")
async def _search_process(
    search_manager_class: SearchManager,
    search_data: SearchPayload,
    only_found=False,
) -> Dict[str, SearchResponse] | SearchResponse:
    if search_data.payload:
        request_id_contextvar.set(search_data)
    logging.info(f"LPOP {search_data}")

    manager = search_manager_class(None, logger=logging)
    await manager.prepare()
    search_result = await manager.search(search_data)

    output = {}
    for module, value in search_result.items():
        if isinstance(value, tuple):
            if not only_found:
                output[module] = KeyDBResponseBuilder.error(e=value[1], code=value[0])
        else:
            output[module] = KeyDBResponseBuilder.ok(value)

    if len(output.keys()) == 1:
        return output[next(iter(output))]

    return output
