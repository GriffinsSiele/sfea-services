import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionBlocked, SessionLocked
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    pass


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    )


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_inactive(session),
    )


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, {"hours": 1}),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionBlocked, account_blocked),
        (SessionLocked, account_locked),
        (Exception, base_exception),
    ]
)
