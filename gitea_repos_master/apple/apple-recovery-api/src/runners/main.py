import asyncio

from worker_classes.logger import Logger

from src.fastapi.schemas import AppleSearchData
from src.logic.apple import AppleSearchManager


async def run():
    Logger().create()

    apple = AppleSearchManager()
    await apple.prepare()
    # result = await apple.search(AppleSearchData(email="tiuha@bk.ru"))
    # result = await apple.search(AppleSearchData(email="kotiphones@gmail.com"))
    result = await apple.search(AppleSearchData(phone="79166367863"))
    # result = await apple.search(AppleSearchData(phone="79687814276"))

    print(result)


if __name__ == "__main__":
    asyncio.run(run())
