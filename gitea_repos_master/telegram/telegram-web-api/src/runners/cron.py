import asyncio
import logging
import random
import time

import aioschedule as schedule
from mongo_client.client import MongoSessions
from pydash import get
from queue_logic.client import KeyDBQueue
from worker_classes.keydb.adapter import KeyDBAdapter
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.logger import Logger
from worker_classes.thread.exception_handler import ExceptionHandler

from src.config.settings import KEYDB_QUEUE, KEYDB_URL, MONGO_DB, MONGO_URL
from src.logic.keydb.fieldXML import field_XML_description
from src.logic.telegram.search_manager import SearchTelegramManager
from src.logic.thread.exception_handler import order_exceptions
from src.runners.__phones import phones


class CronTask:
    def __init__(
        self,
        mongo,
        kdbq,
        exception_handler,
        builder_xml,
    ):
        self.mongo = mongo
        self.kdbq = kdbq
        self.exception_handler = exception_handler
        self.builder_xml = builder_xml

    async def cron(self):
        try:
            mongo = self.mongo

            count = await mongo.count_active()
            logging.info(f"Count sessions: {count}")
            sessions = []
            for _ in range(count):
                sessions.append(await mongo.get_session(next_use_delay=3))

            for session in sessions:
                await self.run(session, random.choice(phones))

            await asyncio.sleep(5)
            after = await mongo.count_active()

            if count != after:
                logging.info("Mismatch session count detected")
                # await TelegramMonitoring(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send(
                #     f"[DEV] Тестирование блокировки Telegram.\nОбнаружена блокировка сессии. Изменения количества сессий: {count} -> {after}."
                # )
        except Exception as e:
            logging.error(e)

    async def run(self, session, payload):
        try:
            logging.info(f"Using session: {session}")
            api = SearchTelegramManager(auth_data=get(session, "session"))
            response = await api.search(payload)
            response = KeyDBAdapter().to_key_db(response, self.builder_xml)

            # await self.kdbq.set_answer(payload, KeyDBResponseBuilder.ok(response))
            await self.mongo.session_success(session)
        except Exception as e:
            await self.exception_handler.call(
                e,
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )
        else:
            await self.exception_handler.normal(
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )


async def run():
    Logger().create()

    kdbq = await KeyDBQueue(
        KEYDB_URL, service=KEYDB_QUEUE, max_allowed_reconnect=5
    ).connect()
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection="telegram-prod", max_allowed_reconnect=5
    ).connect()

    exception_handler = ExceptionHandler(order_exceptions)
    builder_xml = KeyDBBuilderXML(field_XML_description)
    task = CronTask(
        kdbq=kdbq,
        mongo=mongo,
        exception_handler=exception_handler,
        builder_xml=builder_xml,
    )

    await task.cron()

    schedule.every(60).minutes.do(task.cron)

    while True:
        await schedule.run_pending()
        time.sleep(0.1)


if __name__ == "__main__":
    asyncio.run(run())
