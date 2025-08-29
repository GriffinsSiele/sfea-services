import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.pochta.search_manager import SearchPochtaManager


async def run():
    Logger().create()
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()

    for _ in range(1):
        session = await mongo.get_session()
        sem = SearchPochtaManager(session.get("session"))
        await sem.prepare()
        print(await sem.search({"phone": "79374412372"}))


if __name__ == "__main__":
    asyncio.run(run())
