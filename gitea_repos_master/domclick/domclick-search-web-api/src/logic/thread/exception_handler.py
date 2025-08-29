from collections import OrderedDict

from isphere_exceptions import ErrorReturnToQueue
from isphere_exceptions.session import SessionBlocked, SessionEmpty
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(mongo, kdbq, session, payload, api):
    pass


async def no_data_error(mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.empty())
    await mongo.session_success(session, next_use_delay=1)


async def account_blocked(mongo, kdbq, session, payload, api):
    await mongo.session_inactive(session)
    return {"ack": "nack"}


async def no_session_error(mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.no_sessions())


async def incorrect_data(mongo, kdbq, session, payload, api):
    await kdbq.set_answer(
        payload, KeyDBResponseBuilder.error(SourceIncorrectDataDetected())
    )


async def error_return_to_queue(mongo, kdbq, session, payload, api):
    return {"ack": "nack"}


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionBlocked, account_blocked),
        (SessionEmpty, no_session_error),
        (SourceIncorrectDataDetected, incorrect_data),
        (ErrorReturnToQueue, error_return_to_queue),
        (Exception, error_return_to_queue),
    ]
)
