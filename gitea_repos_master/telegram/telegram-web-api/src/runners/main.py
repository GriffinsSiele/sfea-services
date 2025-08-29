import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.telegram.search_manager import SearchTelegramManager


async def run():
    Logger().create()
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION, max_allowed_reconnect=5
    ).connect()
    session = await mongo.get_session()
    r = SearchTelegramManager(session["session"])
    await r.prepare()
    print(await r.search("79519399901"))


if __name__ == "__main__":
    asyncio.run(run())
