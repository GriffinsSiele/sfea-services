import asyncio
from collections import OrderedDict

from isphere_exceptions.session import (
    SessionBlocked,
    SessionInvalidCredentials,
    SessionLimitError,
    SessionLocked,
)
from isphere_exceptions.source import SourceParseError, SourceTimeout
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    await mongo.session_update(session, {"session": api.get_session()})
    if api.next_use:
        await mongo.session_lock(session, api.next_use)


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    )
    await mongo.session_update(session, {"session": api.get_session()})
    if api.next_use:
        await mongo.session_lock(session, api.next_use)


async def limit_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_update(session, {"session": api.get_session()}),
    )


async def timeout_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_update(session, {"session": api.get_session()}),
    )


async def source_parse_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"seconds": 30}),
    )


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_inactive(session),
    )


async def invalid_credentials(exception, mongo, kdbq, session, payload, api):
    await account_blocked(exception, mongo, kdbq, session, payload, api)


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"hours": 10}),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionInvalidCredentials, invalid_credentials),
        (SessionLimitError, limit_error),
        (SourceTimeout, timeout_error),
        (SourceParseError, source_parse_error),
        (SessionBlocked, account_blocked),
        (SessionLocked, account_locked),
        (Exception, base_exception),
    ]
)
