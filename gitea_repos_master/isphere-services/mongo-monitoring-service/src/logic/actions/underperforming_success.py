from pydash import get

from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL, TELEGRAM_CHAT_ID, TELEGRAM_TOKEN_BOT
from src.logic.mongo import MyMongoSessions
from src.logic.mongo.session import is_watching_collection
from src.logic.telegram.messages import TelegramMessages
from src.logic.telegram.telegram import TelegramAPI
from src.logic.utils.decorators import safe


class UnderperformingSuccessAction:
    @safe
    async def call(self):
        mongo = await MyMongoSessions(
            MONGO_URL, db=MONGO_DB, collection=None, max_allowed_reconnect=5
        ).connect()
        mongo.projection = {"count_use": 1, "count_success": 1}

        collections = await mongo.db.list_collection_names()

        data = {"collections": {}}
        for collection in collections:
            if not is_watching_collection(
                collection, ConfigApp.UNDERPERFORMING_SUCCESS_IGNORE_COLLECTIONS
            ):
                continue

            mongo.switch_collection(collection)
            useless_sessions, avg_use = self.__process_collection(
                await mongo.get_sessions()
            )
            data["collections"][collection] = useless_sessions, avg_use

        message = TelegramMessages().underperforming_success(**data)
        await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send(message)

        await mongo.close()

    def __process_collection(self, sessions):
        stats = []
        for session in sessions:
            count_use, count_success = (
                get(session, "count_use") or 1,
                get(session, "count_success") or 0,
            )
            if count_use < ConfigApp.UNDERPERFORMING_SUCCESS_MIN_USE:
                continue
            rate = count_success / count_use
            stats.append((session, rate))

        sessions.clear()

        if not stats:
            return [], 0

        avg_use = sum([s[1] for s in stats]) / len(stats)

        useless_sessions = []
        for session, rate in stats:
            if (
                rate
                < avg_use
                * (100 - ConfigApp.UNDERPERFORMING_SUCCESS_DEVIATION_PERCENT)
                / 100
            ):
                useless_sessions.append({"session": session["_id"], "rate": rate})

        return useless_sessions, avg_use
