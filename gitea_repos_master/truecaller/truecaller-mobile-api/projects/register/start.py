import asyncio
import logging
from asyncio import sleep
from datetime import datetime, timedelta

from livenessprobe_logic import HealthCheck
from mongo_client.client import MongoSessions
from pydash import get, pick, set_
from worker_classes.logger import Logger

from src.config.settings import (
    COUNT_SESSIONS,
    MONGO_COLLECTION,
    MONGO_DB,
    MONGO_URL,
    WEEKEND_REDUCTION_FACTOR,
)
from src.logic.register import RegisterManager


def bound(v, min_v=0, max_v=100):
    return max(min_v, min(v, max_v))


async def actions(mongo, extra_actions, now):
    logging.info(f"Extra actions state: {extra_actions}")
    if not get(extra_actions, f"{now}.dead"):
        await migrate_dead(mongo)
        set_(extra_actions, f"{now}.dead", True)

    return extra_actions


async def register_one(mongo):
    session = await RegisterManager().register()
    if session:
        await mongo.add({"session": {**session, "type": "bulk"}})
        await mongo.add({"session": {**session, "type": "search"}})
        logging.info(f"Created session: {session}")
        HealthCheck().checkpoint()


async def migrate_dead(mongo):
    logging.info("Migrating dead sessions...")
    mongo.default_filter = {"active": False}
    blocked_session = await mongo.get_sessions()
    logging.info(f"Detected {len(blocked_session)} dead sessions")

    mongo_dead = await MongoSessions(
        MONGO_URL, db="dead", collection="truecaller"
    ).connect()
    for session in blocked_session:
        try:
            await mongo_dead.add(session)
        except Exception:
            pass
        try:
            await mongo.session_delete(session)
        except Exception:
            pass
    logging.info("Migration dead sessions done")
    mongo.default_filter = {"active": True}
    await mongo_dead.close()


async def start():
    Logger().create()
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION, max_allowed_reconnect=5
    ).connect()

    extra_actions = {}

    while True:
        now = datetime.today().strftime("%Y-%m-%d")
        is_weekend = datetime.today().weekday() > 4
        extra_actions = pick(extra_actions, now)

        try:
            extra_actions = await actions(mongo, extra_actions, now)
        except Exception as e:
            logging.warning(e)

        count_real_active = await mongo.sessions.count_documents(
            filter={"active": True, **mongo._filter_exclude_lock(offset=10)}
        )

        count = await mongo.sessions.count_documents(filter={"active": True})
        v = COUNT_SESSIONS * (1 if not is_weekend else WEEKEND_REDUCTION_FACTOR) - count
        required_sessions = int(bound(v, min_v=0, max_v=COUNT_SESSIONS) / 2)

        # Сколько заблокировано сессий на 2ч+
        count_temp_lock = await mongo.sessions.count_documents(
            filter={
                "active": True,
                "next_use": {"$gt": datetime.now() + timedelta(seconds=60 * 60 * 2)},
            }
        )

        if count_real_active < 20:
            logging.info(f"Real active sessions: {count_real_active}")
            await register_one(mongo)
            continue

        percent_temp_lock = count_temp_lock / (count or 1) * 100

        if percent_temp_lock > 70:
            logging.info(f"Too many temp lock: {round(percent_temp_lock, 2)}%")
            await register_one(mongo)
            continue

        logging.info(f"Required sessions: {required_sessions}")
        if count >= COUNT_SESSIONS or required_sessions <= 0:
            logging.info(f"Enough active sessions: {count}")
            HealthCheck().checkpoint()
            await sleep(60)
            continue

        await register_one(mongo)


if __name__ == "__main__":
    asyncio.run(start())
