import asyncio
from collections import OrderedDict

from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.session import SessionCaptchaDetected, SessionLocked
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder


async def normal(exception, mongo, kdbq, session, payload, api):
    if api:
        await mongo.session_update(session, {"session": api.get_auth()})


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    actions = [
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    ]
    if api:
        actions.append(mongo.session_update(session, {"session": api.get_auth()}))
    await asyncio.gather(*actions)


async def proxy_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_lock(session, period={"seconds": 10}),
    )


async def account_locked(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_inactive(session),
    )


async def captcha_detected(exception, mongo, kdbq, session, payload, api):
    await asyncio.gather(
        kdbq.set_answer(
            payload,
            KeyDBResponseBuilder.error(exception),
        ),
        mongo.session_update(session, {"session.captcha_block": True}),
    )


async def base_exception(exception, mongo, kdbq, session, payload, api):
    await kdbq.set_answer(
        payload,
        KeyDBResponseBuilder.error(exception),
    )


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (ProxyLocked, proxy_locked),
        (SessionLocked, account_locked),
        (SessionCaptchaDetected, captcha_detected),
        (Exception, base_exception),
    ]
)
