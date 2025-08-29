import logging
from datetime import datetime, timedelta
from typing import Dict

from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL, TELEGRAM_CHAT_ID, TELEGRAM_TOKEN_BOT
from src.logic.mongo import MyMongoSessions
from src.logic.mongo.session import is_watching_collection
from src.logic.telegram.messages import TelegramMessages
from src.logic.telegram.telegram import TelegramAPI
from src.logic.utils.decorators import safe


class CriticalMinWatcher:
    state_critical: Dict[str, datetime] = {}

    @safe
    async def call(self):
        mongo = await MyMongoSessions(
            MONGO_URL, db=MONGO_DB, collection=None, max_allowed_reconnect=5
        ).connect()
        mongo.projection = {"active": 1, "next_use": 1}

        collections = await mongo.db.list_collection_names()

        messages = []
        for collection in collections:
            if not is_watching_collection(
                collection, ConfigApp.CRITICAL_MIN_IGNORE_COLLECTIONS
            ):
                continue

            mongo.switch_collection(collection)
            stats = await mongo.aggregate_statistics()
            logging.info(f"CriticalMinWatcher for {collection}: {stats}")
            message = await self.__process_below_percent(stats, collection)
            if message:
                messages.append(message)

        await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send_many(messages)
        await mongo.close()

    async def __process_below_percent(self, stats, collection):
        stats["count_total"] = stats["count_total"] if stats["count_total"] else 1
        percent = int(stats["count_active"] / stats["count_total"] * 100)

        is_below = percent <= ConfigApp.CRITICAL_MIN_PERCENT_OF_SESSIONS_TO_TRIGGER
        is_first_below_seen = collection not in self.state_critical
        message = ""
        if is_below and (
            is_first_below_seen
            or (
                self.state_critical[collection]
                + timedelta(hours=ConfigApp.CRITICAL_MIN_REPEAT_NOTIFICATION_DELAY)
                <= datetime.now()
            )
        ):
            data = {"prod": collection, "stats": stats}
            message = TelegramMessages().below_normal(**data)
            self.state_critical[collection] = datetime.now()

        return message
