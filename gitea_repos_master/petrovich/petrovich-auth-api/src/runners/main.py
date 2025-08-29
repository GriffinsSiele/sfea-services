import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.petrovich.search_manager import SearchPetrovichManager


async def run():
    Logger().create()
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()

    for i in range(1):
        session = await mongo.get_session()
        # sem = SearchPetrovichManager({"cookies": {"1": "1"}})
        sem = SearchPetrovichManager(session.get('session'))
        await sem.prepare()
        print(await sem.search({"phone": "79208533738"}))
        print(sem.get_session())


if __name__ == "__main__":
    asyncio.run(run())
