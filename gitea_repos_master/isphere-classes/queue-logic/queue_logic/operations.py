from isphere_exceptions.keydb import KeyDBOperationFailure, KeyDBTimeout
from redis.exceptions import TimeoutError as TimeoutErrorRedis

from queue_logic.connect import KeyDBConnectInterface
from queue_logic.logger import logger


def timeout(func):
    """Декоратор для задания таймаута на операции, перехватывания ошибок и
    форматирования их в isphere-exceptions"""

    async def wrapper(*args, **kwargs):
        logger.debug(f"{func.__name__}, {args}, {kwargs}")

        try:
            response = await func(*args, **kwargs)
            logger.debug(f"{func.__name__} response: {response}")
            return response
        except TimeoutErrorRedis as e:
            logger.debug(e)
            raise KeyDBTimeout(str(e))
        except Exception as e:
            logger.debug(e)
            raise KeyDBOperationFailure(str(e))

    return wrapper


class KeyDBOperations(KeyDBConnectInterface):
    """Класс-обертка над основными операциями с redis.

    Используется для задания декораторов и прямого подключения к redis
    """

    @timeout
    async def hget(self, *args, **kwargs):
        return await self.db.hget(*args, **kwargs)

    @timeout
    async def hset(self, *args, **kwargs):
        return await self.db.hset(*args, **kwargs)

    @timeout
    async def lpush(self, *args, **kwargs):
        return await self.db.lpush(*args, **kwargs)

    @timeout
    async def rpush(self, *args, **kwargs):
        return await self.db.rpush(*args, **kwargs)

    @timeout
    async def lpop(self, *args, **kwargs):
        return await self.db.lpop(*args, **kwargs)

    @timeout
    async def execute_command(self, *args, **kwargs):
        return await self.db.execute_command(*args, **kwargs)
