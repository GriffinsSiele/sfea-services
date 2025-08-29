import asyncio

from worker_classes.logger import Logger

from src.logic.google.search_manager_auth import SearchGoogleAuthManager


async def run():
    Logger().create()

    for _ in range(1):
        sm = SearchGoogleAuthManager()
        await sm.prepare()
        print(await sm.is_ready())

        print(await sm.search({"phone": "89150336843"}))
        # await sm.prepare()
        # print(await sm.is_ready())
        # print(await sm.search("+79208313143"))


if __name__ == "__main__":
    asyncio.run(run())
