import asyncio

from queue_logic.client import KeyDBQueue
from rabbitmq_logic.consumer import RabbitMQConsumer
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.sentry.sentry import Sentry
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.thread_manager import ThreadManagerRabbitMQ

from src.config.settings import (
    KEYDB_QUEUE_NAME,
    KEYDB_URL,
    MODE,
    RABBITMQ_CONSUMERS,
    RABBITMQ_QUEUE_NAME,
    RABBITMQ_URL,
    SENTRY_URL_NAME,
)
from src.logger.logger import Logger
from src.logic.google.search_manager_name import SearchGoogleNameManager
from src.logic.keydb.fieldXML_name import field_XML_description
from src.logic.thread.exception_handler import order_exceptions


async def main():
    Logger().create()

    kdbq = await KeyDBQueue(KEYDB_URL, service=KEYDB_QUEUE_NAME).connect()
    sentry = Sentry(SENTRY_URL_NAME, MODE)

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)

    rabbitmq = await RabbitMQConsumer(
        RABBITMQ_URL,
        RABBITMQ_QUEUE_NAME,
        RABBITMQ_CONSUMERS,
    ).connect()

    tm = ThreadManagerRabbitMQ(
        mongo=None,
        kdbq=kdbq,
        rabbitmq=rabbitmq,
        exception_handler=exception_handler,
        builder_xml=builder_xml,
        sentry=sentry,
    )

    await tm.run(
        SearchGoogleNameManager,
        count=-1,
        max_time_to_prepare=30,
        with_update_sessions=False,
    )
    tm.TASK_TIMEOUT = 60


if __name__ == "__main__":
    asyncio.run(main())
