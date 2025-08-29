import asyncio
from pprint import pprint

from worker_classes.logger import Logger

from src.fastapi.schemas import SearchEmailPayload
from src.logic.holehe_search.modules import ACTIVE_MODULES
from src.logic.holehe_search.search import SearchHoleheManager
from src.proxy import proxy_cache_manager


async def main():
    Logger().create()
    await proxy_cache_manager.get_proxy()

    email = f"kovinevmv@gmail.com"
    modules = ACTIVE_MODULES

    s = SearchHoleheManager()
    await s.prepare()
    d = await s.search(SearchEmailPayload(payload=email, modules=modules))
    pprint(d)


asyncio.run(main())
