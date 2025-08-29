import asyncio
from collections import OrderedDict

from isphere_exceptions.session import (
    SessionBlocked,
    SessionCaptchaDecodeError,
    SessionLimitError,
    SessionLocked,
)
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api and api.get_session():
        await mongo.session_update(
            session, {"session": api.get_session(), "next_use": api.next_use()}
        )


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session, next_use_delay=1),
    )

    if api and api.get_session():
        await mongo.session_update(
            session, {"session": api.get_session(), "next_use": api.next_use()}
        )


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    await mongo.session_inactive(session)
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_inactive(session),
    )


async def limit_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"hours": 3}),
    )
    if api:
        api.set_temp_lock()
        await mongo.session_update(
            session, {"session": api.get_session(), "next_use": api.next_use()}
        )


async def captcha_decode_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"hours": 0.1}),
    )


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"minutes": 10}),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))
    if session:
        await mongo.session_lock(session, period={"minutes": 1})


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionBlocked, account_blocked),
        (SessionLimitError, limit_error),
        (SessionCaptchaDecodeError, captcha_decode_error),
        (SessionLocked, account_locked),
        (Exception, base_exception),
    ]
)
