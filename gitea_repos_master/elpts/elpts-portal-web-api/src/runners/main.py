import asyncio

from worker_classes.logger import Logger

from src.logic.elpts.search_manager_vin import ElPtsSearchManagerVin


async def run():
    Logger().create()

    elpts = ElPtsSearchManagerVin()
    await elpts.prepare()
    r = await elpts.search("LVTDD24B4PD090302")
    print(r)


asyncio.run(run())
