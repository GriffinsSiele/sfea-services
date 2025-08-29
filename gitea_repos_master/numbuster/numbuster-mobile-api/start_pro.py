import asyncio

from mongo_client.client import MongoSessions
from queue_logic.client import KeyDBQueue
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.thread_manager import ThreadManagerKeyDB

from src.config.settings import (
    KEYDB_QUEUE_PRO,
    KEYDB_TTL_OK,
    KEYDB_URL,
    MODE,
    MONGO_COLLECTION_PRO,
    MONGO_DB,
    MONGO_URL,
    SENTRY_URL,
)
from src.logic.keydb.fieldXML import field_XML_description
from src.logic.numbuster.search_pro_manager import SearchNumbusterProManager
from src.logic.sentry.sentry import SentryConfig
from src.logic.thread.exception_handler import order_exceptions


async def main():
    Logger().create(sensitive_fields=["json:access_token", "json:fcm_token"])

    kdbq = await KeyDBQueue(
        KEYDB_URL, service=KEYDB_QUEUE_PRO, max_allowed_reconnect=5
    ).connect()
    kdbq.task_ttl_ok = KEYDB_TTL_OK

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION_PRO, max_allowed_reconnect=5
    ).connect()
    mongo.next_use_delay = 45

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)

    sentry = Sentry(SENTRY_URL, MODE, ignore_errors=SentryConfig.IGNORE_ERRORS)

    tm = ThreadManagerKeyDB(
        mongo=mongo,
        kdbq=kdbq,
        exception_handler=exception_handler,
        builder_xml=builder_xml,
        sentry=sentry,
    )

    await tm.run(
        SearchNumbusterProManager,
        max_time_to_prepare=1,
        with_update_sessions=True,
        count=1,
    )


if __name__ == "__main__":
    asyncio.run(main())
