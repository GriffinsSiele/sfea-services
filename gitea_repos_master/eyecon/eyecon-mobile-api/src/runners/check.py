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
                "e_auth": "c4b9941e-522c-4ed8-85cb-0bcc663dea65",
                "e_auth_c": 29,
                "phone": "79773539941",
            }
        }
    )


if __name__ == "__main__":
    asyncio.run(run())
