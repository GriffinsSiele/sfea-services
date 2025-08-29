import asyncio
from pprint import pprint
from time import sleep

from mongo_client.client import MongoSessions
from pydash import get
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.numbuster.search_manager import SearchNumbusterManager


async def main():
    Logger().create(sensitive_fields=["json:access_token", "json:fcm_token"])

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()

    for i in range(1):
        session = await mongo.get_session()

        try:
            sm = SearchNumbusterManager(get(session, "session"))
            await sm.prepare()
            response = await sm.search("79208313140")
            pprint(response)
        except Exception as e:
            print(e)
        sleep(1)


if __name__ == "__main__":
    asyncio.run(main())
