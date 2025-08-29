import asyncio
import logging
from asyncio import sleep

from mongo_client.client import MongoSessions
from pydash import get
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.captcha.manager import CaptchaManager

MAX_RETRY_COUNT_ACTIVATE = 5


async def run():
    Logger().create()
    logging.info("Worker started")

    mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
    mongo.default_filter = {"session.captcha_block": True, "active": True}

    while True:
        try:
            sessions = await mongo.get_sessions()

            for session in sessions:
                logging.info(f"Activating session: {session}")
                for _ in range(MAX_RETRY_COUNT_ACTIVATE):
                    cm = CaptchaManager(get(session, "session"))
                    status = await cm.activate()
                    if status:
                        await mongo.session_update(session, {"session": cm.get_auth()})
                        logging.info("Success")
                        break

        except Exception as e:
            logging.error(e)

        logging.info("Sleeping...")
        await sleep(60)


if __name__ == "__main__":
    asyncio.run(run())
