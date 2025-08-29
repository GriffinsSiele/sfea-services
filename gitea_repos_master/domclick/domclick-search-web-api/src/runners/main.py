import asyncio
from time import sleep

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.domclick.search_manager import SearchDomclickManager


async def run():
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    session = await mongo.get_session()
    for i in range(500, 501):
        phone = f"79208533742"
        try:
            sem = SearchDomclickManager(session.get("session"))
            print(phone, await sem.search(phone))
        except Exception as e:
            print(phone, e)

        sleep(5)


asyncio.run(run())
