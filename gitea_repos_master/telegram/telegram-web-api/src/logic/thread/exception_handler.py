import asyncio
from collections import OrderedDict

from isphere_exceptions.session import SessionBlocked, SessionLocked
from isphere_exceptions.success import NoDataEvent
from worker_classes.keydb.response_builder import KeyDBResponseBuilder

from src.logic.mongo.filter import update_filter


async def normal(exception, mongo, kdbq, session, payload, api):
    if api:
        session_update = {"session": api.get_session(), "next_use": api.calc_next_use()}
        await asyncio.gather(
            mongo.session_update(session, session_update),
            api.close_session(),
        )
        update_filter(mongo)


async def no_data_error(exception, mongo, kdbq, session, payload, api):
    actions = [
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_success(session),
    ]
    if api:
        session_update = {"session": api.get_session(), "next_use": api.calc_next_use()}
        actions.extend(
            [
                mongo.session_update(session, session_update),
                api.close_session(),
            ]
        )

    await asyncio.gather(*actions)


async def account_blocked(exception, mongo, kdbq, session, payload, api):
    actions = [
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_inactive(session),
    ]
    if api:
        actions.append(api.close_session())
    await asyncio.gather(*actions)


async def account_locked(exception, mongo, kdbq, session, payload, api):
    actions = [
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
        mongo.session_lock(session, period={"hours": 6}),
    ]
    if api:
        actions.append(api.close_session())
    await asyncio.gather(*actions)


async def base_exception(exception, mongo, kdbq, session, payload, api):
    actions = [
        kdbq.set_answer(payload, KeyDBResponseBuilder.error(exception)),
    ]
    if api:
        actions.append(api.close_session())
    await asyncio.gather(*actions)


order_exceptions = OrderedDict(
    [
        (None, normal),
        (NoDataEvent, no_data_error),
        (SessionBlocked, account_blocked),
        (SessionLocked, account_locked),
        (Exception, base_exception),
    ]
)
