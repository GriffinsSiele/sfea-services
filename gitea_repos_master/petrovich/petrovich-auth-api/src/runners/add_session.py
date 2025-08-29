import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.petrovich.authorizer import Authorizer


async def run():
    Logger().create()
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()

    for i in range(5):
        auth = Authorizer(None)
        await auth.create_new_session()
        await mongo.add({"session": {"cookies": auth.cookies}})


if __name__ == "__main__":
    asyncio.run(run())
