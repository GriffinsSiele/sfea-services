import functools

from isphere_exceptions.mongo import MongoOperationFailure, MongoTimeout
from pymongo.errors import NetworkTimeout

from mongo_client.connect import MongoConnectInterface
from mongo_client.logger import logger


def reconnect(func):
    @functools.wraps(func)
    async def wrapper(self, *args, **kwargs):
        async def action(*args, **kwargs):
            response = await func(self, *args, **kwargs)
            logger.debug(f"Operation response: {response}")
            return response

        logger.debug(f"Operation '{func.__name__}' with args: {args}, {kwargs}")
        try:
            return await action(*args, **kwargs)
        except NetworkTimeout as e:
            logger.debug(e)

            if self.allow_reconnect:
                logger.debug("Reconnecting...")
                try:
                    return await action(*args, **kwargs)
                except NetworkTimeout as e:
                    logger.debug(e)

            raise MongoTimeout()
        except Exception as e:
            logger.debug(e)
            raise MongoOperationFailure()

    return wrapper


class MongoOperations(MongoConnectInterface):
    """Класс-обертка над основными операциями с mongo.

    Используется для задания декораторов и прямого подключения к mongo
    """

    def __init__(self, *args, **kwargs):
        self.allow_reconnect = kwargs.pop("allow_reconnect", False)
        super().__init__(*args, **kwargs)

    @reconnect
    async def count_documents(self, *args, **kwargs):
        return await self.sessions.count_documents(*args, **kwargs)

    @reconnect
    async def update_one(self, *args, **kwargs):
        return await self.sessions.update_one(*args, **kwargs)

    @reconnect
    async def delete_one(self, *args, **kwargs):
        return await self.sessions.delete_one(*args, **kwargs)

    @reconnect
    async def insert_one(self, *args, **kwargs):
        return await self.sessions.insert_one(*args, **kwargs)

    @reconnect
    async def find_one_and_update(self, *args, **kwargs):
        return await self.sessions.find_one_and_update(*args, **kwargs)

    @reconnect
    async def index_information(self, *args, **kwargs):
        return await self.sessions.index_information(*args, **kwargs)

    @reconnect
    async def create_index(self, *args, **kwargs):
        return await self.sessions.create_index(*args, **kwargs)

    @reconnect
    async def drop_index(self, *args, **kwargs):
        return await self.sessions.drop_index(*args, **kwargs)

    @reconnect
    async def find(self, *args, **kwargs):
        return [d async for d in self.sessions.find(*args, **kwargs)]
