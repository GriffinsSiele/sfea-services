import asyncio

from mongo_client.client import MongoSessions
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL


async def run():
    Logger().create()
    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()

    await mongo.add(
        {
            "session": {
                "cookies": {"sessionId": "", "CAS_ID_SIGNED": ""},
                "proxy_id": -1,
                "phone": "79317096125",
            }
        }
    )


asyncio.run(run())
