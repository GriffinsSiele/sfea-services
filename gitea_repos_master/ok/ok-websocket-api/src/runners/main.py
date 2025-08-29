import asyncio

from worker_classes.logger import Logger

from src.logic.ok.search_manager import SearchOKManager


async def run():
    Logger().create()

    sokm = SearchOKManager(proxy=True)
    await sokm.prepare()
    response = await sokm.search({"phone": "79206075774"})
    print(response)


if __name__ == "__main__":
    asyncio.run(run())
