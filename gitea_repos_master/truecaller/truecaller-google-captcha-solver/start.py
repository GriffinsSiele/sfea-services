import asyncio
import logging

from mongo_client.client import MongoSessions
from pydash import filter_, get, uniq_by

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.activation.activation import ActivationManager
from src.logic.logger import Logger


async def start():
    Logger().create()
    logging.info("Start")
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()
    mongo.default_filter = {"active": False}

    sessions = await mongo.get_sessions()

    uniq_sessions = uniq_by(sessions, lambda x: get(x, "session.token"))

    for i, session in enumerate(uniq_sessions):
        token = session["session"]["token"]
        logging.info(f"{i}/{len(uniq_sessions)}: {token}")

        is_ok = ActivationManager(token).start()
        logging.info(f"Status activation: {is_ok}")

        if is_ok:
            logging.info("Updating sessions")
            sessions_activate = filter_(
                sessions, lambda x: get(x, "session.token") == token
            )
            logging.info(f"Sessions: {sessions_activate}")
            for s in sessions_activate:
                await mongo.session_update(s, {"active": True})


asyncio.run(start())
