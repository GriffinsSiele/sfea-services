import asyncio

from queue_logic.client import KeyDBQueue
from rabbitmq_logic.consumer import RabbitMQConsumer
from rabbitmq_logic.publisher import RabbitMQPublisher
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.thread_manager import ThreadManagerRabbitMQ

from lib.src.logic.mongo.mongo import MongoSessions
from src.config.settings import (
    KEYDB_QUEUE,
    KEYDB_TTL_OK,
    KEYDB_URL,
    MODE,
    MONGO_COLLECTION,
    MONGO_DB,
    MONGO_URL,
    RABBITMQ_CONSUMERS,
    RABBITMQ_QUEUE,
    RABBITMQ_QUEUE_SESSION,
    RABBITMQ_URL,
    SENTRY_URL_WORKER,
)
from src.logic.keydb.fieldXML import field_XML_description
from src.logic.ok.search_manager import SearchOKManager
from src.logic.thread.exception_handler import order_exceptions


async def main():
    Logger().create()

    kdbq = await KeyDBQueue(
        keydb_url=KEYDB_URL, service=KEYDB_QUEUE, max_allowed_reconnect=5
    ).connect()
    kdbq.task_ttl_ok = KEYDB_TTL_OK

    mongo = await MongoSessions(
        MONGO_URL, MONGO_DB, MONGO_COLLECTION, max_allowed_reconnect=5
    ).connect()

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)

    rabbitmq_session = await RabbitMQPublisher(
        RABBITMQ_URL, RABBITMQ_QUEUE_SESSION
    ).connect()

    rabbitmq = await RabbitMQConsumer(
        RABBITMQ_URL, RABBITMQ_QUEUE, RABBITMQ_CONSUMERS
    ).connect()

    sentry = Sentry(SENTRY_URL_WORKER, MODE)

    tm = ThreadManagerRabbitMQ(
        mongo=mongo,
        kdbq=kdbq,
        rabbitmq=rabbitmq,
        exception_handler=exception_handler,
        builder_xml=builder_xml,
        sentry=sentry,
    )

    await tm.run(
        SearchOKManager,
        rabbitmq=rabbitmq_session,
        max_time_to_prepare=1,
        with_update_sessions=True,
        count=RABBITMQ_CONSUMERS,
    )


if __name__ == "__main__":
    asyncio.run(main())
