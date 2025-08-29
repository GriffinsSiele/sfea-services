import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api and session:
        await mongo.session_update(session, {"session": api.get_session()})


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.empty()),
        mongo.session_success(session),
    )
    if api and session:
        await mongo.session_update(session, {"session": api.get_session()})


async def session_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.empty()),
        mongo.session_lock(session),
    )
    if api and session:
        await mongo.session_update(session, {"session": api.get_session()})


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(
        payload,
        KeyDBResponseBuilder.error(exception),
    )


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionBlocked, session_locked),
        (Exception, base_exception),
    ]
)
