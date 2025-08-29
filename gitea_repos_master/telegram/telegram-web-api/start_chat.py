import asyncio
import logging
import random
from asyncio import sleep
from datetime import datetime

from livenessprobe_logic import HealthCheck
from mongo_client.client import MongoSessions
from pydash import get
from worker_classes.logger import Logger

from src.config.app import ConfigApp
from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.sentry.sentry import Sentry
from src.logic.telegram.dialog_manager import DialogManager


async def main():
    Logger().create()
    Sentry().create()

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION, max_allowed_reconnect=5
    ).connect()
    counter = 0

    while counter < 20:
        try:
            mongo.default_sort = [("session.last_message", 1)]
            session_1 = await mongo.get_session(0)
            logging.info(f"Session 1: {session_1}")

            friends_1 = get(session_1, "session.friends", []) or []
            logging.info(f"Session 1 has {len(friends_1)} friends: {friends_1}")

            if len(friends_1) < ConfigApp.MAX_FRIENDS_FOR_CHAT:
                mongo.default_sort = [("last_use", 1)]
                session_2 = await mongo.get_session(0, next_use_delay=-1)
                logging.info(f"Possible new friend: {session_2}")
                if session_2 and session_2["_id"] != session_1["_id"]:
                    friends_1.append(session_2["_id"])
                    logging.info(f"Session 1 insert 1 friend: {session_2}")

            friend_id = random.choice(friends_1)
            logging.info(f"Starting dialog with user: {friend_id}")
            mongo.default_filter = {"_id": friend_id}
            session_2 = await mongo.get_session(0, next_use_delay=-1)
            friends_2 = get(session_2, "session.friends", []) + [session_1["_id"]]

            logging.info(f"User 1: {session_1}")
            logging.info(f"User 2: {session_2}")

            if not session_1 or not session_2:
                continue

            await DialogManager(
                get(session_1, "session"), get(session_2, "session")
            ).start()

            update_1 = {
                "session.last_message": datetime.now(),
                "session.friends": list(set(friends_1))[: ConfigApp.MAX_FRIENDS_FOR_CHAT],
            }
            await mongo.session_update(session_1, update_1)
            update_2 = {
                "session.last_message": datetime.now(),
                "session.friends": list(set(friends_2))[: ConfigApp.MAX_FRIENDS_FOR_CHAT],
            }
            await mongo.session_update(session_2, update_2)
        except Exception as e:
            logging.error(e)

        HealthCheck().checkpoint()
        sleep_time = random.randint(60 * 2, 60 * 10)
        logging.info(f"Sleeping {sleep_time} seconds")
        await sleep(sleep_time)
        counter += 1


if __name__ == "__main__":
    asyncio.run(main())
