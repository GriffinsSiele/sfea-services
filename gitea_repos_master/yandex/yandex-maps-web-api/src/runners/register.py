import asyncio
import logging

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.yandex.authorizer import AuthorizerYandexMaps


async def run():
    Logger().create()

    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    for _ in range(1):
        try:
            aym = AuthorizerYandexMaps(proxy=True)
            await aym._prepare_proxy()
            await aym.set_auth()
            await mongo.add({"session": aym.get_session()})
        except Exception as e:
            logging.error(e)


if __name__ == "__main__":
    asyncio.run(run())
