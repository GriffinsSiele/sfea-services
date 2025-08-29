import asyncio

from queue_logic.client import KeyDBQueue
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.thread_manager import ThreadManagerKeyDB

from src.config.settings import KEYDB_QUEUE, KEYDB_URL, MODE, SENTRY_URL
from src.logic.keydb.fieldXML import field_XML_description
from src.logic.ok.search_manager import SearchOKManager
from src.logic.thread.exception_handler import order_exceptions


async def main():
    Logger().create()

    kdbq = await KeyDBQueue(
        KEYDB_URL, service=KEYDB_QUEUE, max_allowed_reconnect=5
    ).connect()

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)

    sentry = Sentry(SENTRY_URL, MODE)

    tm = ThreadManagerKeyDB(
        mongo=None,
        kdbq=kdbq,
        exception_handler=exception_handler,
        builder_xml=builder_xml,
        sentry=sentry,
    )
    tm.TASK_TIMEOUT = 45

    await tm.run(
        SearchOKManager,
        proxy=True,
        with_update_sessions=False,
        max_time_to_prepare=1,
        count=2,
    )


if __name__ == "__main__":
    asyncio.run(main())
