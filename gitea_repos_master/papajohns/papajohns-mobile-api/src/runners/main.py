import asyncio
import logging

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.papajohns.search_manager import SearchPapaJohnsManager


async def run():
    Logger().create(sensitive_fields=["json:device_token", "json:ja3"])
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()

    for i in range(38, 39):
        session = await mongo.get_session()
        sp = SearchPapaJohnsManager(
            auth_data=session.get("session"),
        )
        await sp.prepare()
        logging.info(await sp.search(f"+792085337{i}"))


if __name__ == "__main__":
    asyncio.run(run())
