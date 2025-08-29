import asyncio

from queue_logic.client import KeyDBQueue
from rabbitmq_logic.consumer import RabbitMQConsumer
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.thread_manager import ThreadManagerRabbitMQ

from src.config.settings import (
    KEYDB_QUEUE_HUAWEI,
    KEYDB_URL,
    MODE,
    RABBITMQ_CONSUMERS,
    RABBITMQ_QUEUE_HUAWEI,
    RABBITMQ_URL,
    SENTRY_URL_HUAWEI,
)
from src.logic.huawei.search_manager_huawei import SearchHuaweiManager
from src.logic.keydb.fieldXML import field_XML_description
from src.logic.thread.exception_handler import order_exceptions


async def main():
    Logger().create()

    kdbq = await KeyDBQueue(KEYDB_URL, service=KEYDB_QUEUE_HUAWEI).connect()
    sentry = Sentry(SENTRY_URL_HUAWEI, MODE)

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)

    rabbitmq = await RabbitMQConsumer(
        RABBITMQ_URL,
        RABBITMQ_QUEUE_HUAWEI,
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
    tm.TASK_TIMEOUT = 60

    await tm.run(
        SearchHuaweiManager,
        count=-1,
        max_time_to_prepare=30,
        with_update_sessions=False,
    )


if __name__ == "__main__":
    asyncio.run(main())
