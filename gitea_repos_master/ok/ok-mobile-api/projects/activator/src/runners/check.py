import asyncio
import logging
from dataclasses import dataclass

from worker_classes.logger import Logger

from lib.src.logic.mongo.mongo import MongoSessions
from src.config.settings import MONGO_URL
from src.logic.rabbitmq_hook import RabbitMQHook


@dataclass
class Message:
    body = None


async def run():
    Logger().create()

    mongo = MongoSessions(MONGO_URL, db="dead", collection="ok-mobile")
    await mongo.connect()
    mongo.default_filter = {"active": False}
    r = RabbitMQHook(mongo)

    for _ in range(20):
        session = await mongo.get_session()
        logging.info(session)

        m = Message()
        m.body = session.get("session")

        token = await r.process(m)
        print(token)


if __name__ == "__main__":
    asyncio.run(run())
