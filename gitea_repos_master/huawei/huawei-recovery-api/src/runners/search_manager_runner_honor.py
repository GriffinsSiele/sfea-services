import asyncio

from worker_classes.logger import Logger

from src.logic.huawei.search_manager_honor import SearchHonorManager


async def run():
    Logger().create()

    for i in range(1):
        sem = SearchHonorManager()
        await sem.prepare()
        print(await sem.search({"phone": "79682606894"}))
        input("Press Enter to exit")


if __name__ == "__main__":
    asyncio.run(run())
