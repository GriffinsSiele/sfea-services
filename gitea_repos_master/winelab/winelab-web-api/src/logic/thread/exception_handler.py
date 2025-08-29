import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionLocked
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session, next_use_delay=1),
    )
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionLocked, account_locked),
        (Exception, base_exception),
    ]
)
