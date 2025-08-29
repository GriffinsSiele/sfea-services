from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL, TELEGRAM_CHAT_ID, TELEGRAM_TOKEN_BOT
from src.logic.mongo import MyMongoSessions
from src.logic.mongo.session import is_watching_collection
from src.logic.telegram.messages import TelegramMessages
from src.logic.telegram.telegram import TelegramAPI
from src.logic.utils.decorators import safe


class StatisticsAction:
    @safe
    async def call(self):
        mongo = await MyMongoSessions(
            MONGO_URL, db=MONGO_DB, collection=None, max_allowed_reconnect=5
        ).connect()
        mongo.projection = {"active": 1, "next_use": 1}

        collections = await mongo.db.list_collection_names()

        data = {"collections": {}}
        for collection in collections:
            if not is_watching_collection(
                collection, ConfigApp.STATISTICS_IGNORE_COLLECTIONS
            ):
                continue

            mongo.switch_collection(collection)
            stats = await mongo.aggregate_statistics()
            data["collections"][collection] = stats

        message = TelegramMessages().statistics(**data)
        await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send(message)

        await mongo.close()
