import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.avito.search_manager import SearchAvitoManager


async def run():
    Logger().create()

    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    session = await mongo.get_session()
    for _ in range(1):
        sm = SearchAvitoManager(session["session"])
        await sm.prepare()
        print(await sm.search({"phone": "79771015508"}))


if __name__ == "__main__":
    asyncio.run(run())
