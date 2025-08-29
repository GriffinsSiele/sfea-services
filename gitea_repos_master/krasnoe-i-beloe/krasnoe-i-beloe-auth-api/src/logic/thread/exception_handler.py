import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


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
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_inactive(session),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(
        payload,
        KeyDBResponseBuilder.error(exception),
    )


order_exceptions = OrderedDict(
    [
        (None, normal),
        (SourceIncorrectDataDetected, incorrect_error),
        (SessionBlocked, account_blocked),
        (NoDataEvent, no_data_error),
        (Exception, base_exception),
    ]
)
