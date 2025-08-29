import asyncio
from collections import OrderedDict

from isphere_exceptions.session import (
    SessionBlocked,
    SessionCaptchaDetected,
    SessionLocked,
)
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    )
    if api:
        await mongo.session_update(session, {"session": api.get_session()})


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"minutes": 1}),
    )


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"hours": 6}),
    )


async def captcha_detected(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"minutes": 2}),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception))


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionLocked, account_locked),
        (SessionBlocked, account_blocked),
        (SessionCaptchaDetected, captcha_detected),
        (Exception, base_exception),
    ]
)
