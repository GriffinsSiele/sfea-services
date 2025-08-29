import asyncio

from worker_classes.logger import Logger

from src.logic.huawei.search_manager_huawei import SearchHuaweiManager


async def run():
    Logger().create()

    for i in range(1):
        sem = SearchHuaweiManager()
        await sem.prepare()
        print(await sem.search({"phone": "79773539941"}))


if __name__ == "__main__":
    asyncio.run(run())
