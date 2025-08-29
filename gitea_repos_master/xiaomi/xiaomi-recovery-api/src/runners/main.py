import asyncio

from worker_classes.logger import Logger

from src.logic.xiaomi.search_manager import SearchXiaomiManager


async def run():
    Logger().create()

    for _ in range(1):
        sm = SearchXiaomiManager()
        await sm.prepare()
        print(await sm.search({"phone": "79771015508"}))


if __name__ == "__main__":
    asyncio.run(run())
