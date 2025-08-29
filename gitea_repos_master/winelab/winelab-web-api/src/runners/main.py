import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.winelab.search_manager import SearchWinelabManager


async def run():
    Logger().create()
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    session = await mongo.get_session()

    try:
        sem = SearchWinelabManager(session.get("session"))
        await sem.prepare()
        print(await sem.search({"email": f"kovinevmv@gmail.com"}))
        print(sem.get_session())
    except Exception as e:
        print(e)


if __name__ == "__main__":
    asyncio.run(run())
