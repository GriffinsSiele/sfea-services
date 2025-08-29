import asyncio

from rabbitmq_logic.consumer import RabbitMQConsumer
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry

from lib.src.logic.mongo.mongo import MongoSessions
from src.config.settings import (
    MODE,
    MONGO_COLLECTION,
    MONGO_DB,
    MONGO_URL,
    RABBITMQ_QUEUE_SESSION,
    RABBITMQ_URL,
    SENTRY_URL_ACTIVATOR,
)
from src.logic.rabbitmq_hook import RabbitMQHook


async def main():
    Logger().create()

    Sentry(SENTRY_URL_ACTIVATOR, MODE).create()

    mongo = MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION, max_allowed_reconnect=5
    )
    mongo.default_filter = {}
    await mongo.connect()

    rabbitmq_hook = RabbitMQHook(mongo)

    rabbitmq = RabbitMQConsumer(
        RABBITMQ_URL,
        RABBITMQ_QUEUE_SESSION,
        on_message_callback=rabbitmq_hook.process,
        consumer_count=1,
    )
    await rabbitmq.connect()

    await rabbitmq.run()


if __name__ == "__main__":
    asyncio.run(main())
