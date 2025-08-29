import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.simpler.search_manager import SearchSimplerManager


async def run():
    Logger().create()
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()
    session = await mongo.get_session()
    r = SearchSimplerManager(session["session"])
    await r.prepare()
    print(await r.search({"phone": "79208313140"}))


if __name__ == "__main__":
    asyncio.run(run())
