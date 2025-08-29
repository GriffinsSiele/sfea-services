import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.truecaller.search_manager import SearchTruecallerManager


async def run():
    Logger().create()

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()
    session = await mongo.get_session()
    stm = SearchTruecallerManager(session["session"])
    await stm.prepare()

    print(await stm.search("79208533738"))


if __name__ == "__main__":
    asyncio.run(run())
