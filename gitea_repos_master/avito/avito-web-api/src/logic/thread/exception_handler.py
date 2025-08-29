import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionEmpty
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder

from src.logic.adapters.response import LimitError


async def normal(exception, mongo, kdbq, session, payload, api):
    pass


async def limit_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_lock(session, period={"seconds": 60}),
    )


async def incorrect_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_success(session),
    )


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    )


async def no_session_error(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(
        payload,
        KeyDBResponseBuilder.error(exception),
    )


order_exceptions = OrderedDict(
    [
        (None, normal),
        (LimitError, limit_error),
        (SourceIncorrectDataDetected, incorrect_error),
        (NoDataEvent, no_data_error),
        (SessionEmpty, no_session_error),
        (Exception, base_exception),
    ]
)
