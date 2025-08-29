from typing import Optional

import motor.motor_asyncio
from isphere_exceptions.mongo import MongoConfigurationInvalid, MongoConnection
from pymongo.errors import ServerSelectionTimeoutError

from mongo_client.logger import logger


class MongoConnectInterface:
    def __init__(
        self, mongo_url: str, db: str, collection: Optional[str], *args, **kwargs
    ):
        super().__init__()
        logger.debug(f"Configuration: {mongo_url}, {db}, {collection}")

        if not mongo_url or not db:
            raise MongoConfigurationInvalid()

        self.client: motor.AsyncIOMotorClient = motor.motor_asyncio.AsyncIOMotorClient(
            mongo_url,
            connect=True,
            connectTimeoutMS=10_000,
            serverSelectionTimeoutMS=5_000,
            maxIdleTimeMS=10_000,
            maxPoolSize=30,
            minPoolSize=1,
            maxConnecting=5,
            timeoutMS=10_000,
            socketTimeoutMS=10_000,
        )
        logger.debug(f"Mongo Client: {self.client}")

        self.db: motor.AsyncIOMotorDatabase = self.client[db]
        logger.debug(f"Mongo DB: {self.db}")

        self.sessions: motor.AsyncIOMotorCollection = (
            self.db[collection] if collection else None
        )
        logger.debug(f"Mongo collection: {self.sessions}")

    async def connect(self):
        try:
            await self.client.start_session()
        except ServerSelectionTimeoutError:
            raise MongoConnection()
        logger.debug("Connected to MongoDB")
        return self

    async def __aexit__(self, *args, **kwargs):
        await self.close()

    async def close(self):
        logger.debug("Close connection")
        self.client.close()
