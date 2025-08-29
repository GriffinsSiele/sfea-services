import logging
from datetime import datetime
from typing import Dict

from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL, TELEGRAM_CHAT_ID, TELEGRAM_TOKEN_BOT
from src.logic.mongo import MyMongoSessions
from src.logic.mongo.session import is_watching_collection
from src.logic.telegram.messages import TelegramMessages
from src.logic.telegram.telegram import TelegramAPI
from src.logic.utils.decorators import safe


class InactiveWatcher:
    state_inactive: Dict[str, tuple] = {}

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
                collection, ConfigApp.INACTIVE_IGNORE_COLLECTIONS
            ):
                continue

            mongo.switch_collection(collection)
            stats = await mongo.aggregate_statistics()
            logging.info(f"InactiveWatcher for {collection}: {stats}")
            message = await self.__process_inactive(stats, collection)
            if message:
                messages.append(message)

        await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send_many(messages)
        await mongo.close()

    async def __process_inactive(self, stats, collection):
        message = ""

        if collection in self.state_inactive:
            prev_state = self.state_inactive[collection][1]
            is_increased_count_of_blocked = (
                stats["count_blocked"] > prev_state["count_blocked"]
                and stats["count_blocked"]
            )
            if is_increased_count_of_blocked:

                def trim_microseconds(time):
                    return str(time)[:-7]

                data = {
                    "prod": collection,
                    "start": trim_microseconds(self.state_inactive[collection][0]),
                    "end": trim_microseconds(datetime.now()),
                    "before": prev_state,
                    "after": stats,
                }
                message = TelegramMessages().locked(**data)

        self.state_inactive[collection] = (datetime.now(), stats)
        return message
