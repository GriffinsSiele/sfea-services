import asyncio

from mongo_client.client import MongoSessions
from pydash import get
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.doublegis.search_manager import Search2GISManager


async def run():
    Logger().create()

    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    session = await mongo.get_session()

    s2gm = Search2GISManager(get(session, "session"))
    await s2gm.prepare()
    response = await s2gm.search({"phone": "77017185124"})
    print(response)


if __name__ == "__main__":
    asyncio.run(run())
