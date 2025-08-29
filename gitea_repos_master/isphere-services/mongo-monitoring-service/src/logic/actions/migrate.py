import logging
from datetime import datetime, timedelta
from typing import Dict

from pydash import filter_, find

from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL, TELEGRAM_CHAT_ID, TELEGRAM_TOKEN_BOT
from src.logic.mongo import MyMongoSessions
from src.logic.mongo.session import is_watching_collection
from src.logic.telegram.messages import TelegramMessages
from src.logic.telegram.telegram import TelegramAPI
from src.logic.utils.decorators import safe


class MigrateAction:
    next_use_migration_state: Dict[str, datetime] = {}

    @safe
    async def call(self):
        mongo = await MyMongoSessions(
            MONGO_URL, db=MONGO_DB, collection=None, max_allowed_reconnect=5
        ).connect()

        collections = await mongo.db.list_collection_names()
        for collection in filter_(collections, lambda c: c.endswith(ConfigApp.PROD)):
            if not is_watching_collection(
                collection, ConfigApp.MIGRATION_IGNORE_COLLECTIONS
            ):
                continue
            collection = collection.replace("-" + ConfigApp.PROD, "")
            await self._process_collection(mongo, collection, collections)

        await mongo.close()

    async def _process_collection(self, mongo, collection, collections):
        prod = find(collections, lambda x: x == f"{collection}-{ConfigApp.PROD}")
        dev = find(collections, lambda x: x == f"{collection}-{ConfigApp.DEV}")

        if not prod or not dev:
            logging.info(
                f"Stopped. Not found pairs: ({ConfigApp.PROD}={prod}, {ConfigApp.DEV}={dev})"
            )
            return

        if self.is_unsolvable_migration(collection):
            logging.info(
                f"Stopped. Cannot solve lack of session due to limits of next use. Current state: {self.next_use_migration_state}"
            )
            return

        mongo.switch_collection(prod)
        stats_prod_before = await mongo.aggregate_statistics()
        count_active_prod = stats_prod_before["count_active"]
        logging.info(f"{prod} state: {stats_prod_before}")

        if count_active_prod > 0:
            logging.info(f"Stopped. {prod} has enough sessions")
            return

        mongo.switch_collection(dev)
        stats_dev_before = await mongo.aggregate_statistics()
        count_active_dev = stats_dev_before["count_active"]
        logging.info(f"{dev} state: {stats_dev_before}")

        if count_active_dev < ConfigApp.MIGRATION_DEV_MIN_SESSIONS:
            logging.info("Stopped. Not enough sessions in dev")
            self.next_use_migration_state[collection] = datetime.now() + timedelta(
                hours=1
            )
            data = {
                "prod": prod,
                "dev": dev,
                "stats_prod": stats_prod_before,
                "stats_dev": stats_dev_before,
            }
            message = TelegramMessages().migration_failure(**data)
            await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send(message)
            return

        required_count_sessions = int(
            count_active_dev
            * 0.01
            * ConfigApp.MIGRATION_PERCENT_SESSIONS_FROM_DEV_SESSIONS
        )
        mongo.projection = None

        sessions = []
        for _ in range(required_count_sessions):
            session = await mongo.get_session()
            logging.info(f"Extracting session: {session}")
            sessions.append(session)
            await mongo.session_delete(session)

        mongo.switch_collection(prod)
        for session in sessions:
            await mongo.add(session)
            logging.info(f"Adding session: {session}")

        stats_after_prod = await mongo.aggregate_statistics()
        mongo.switch_collection(dev)
        stats_dev_after = await mongo.aggregate_statistics()

        data = {
            "prod": prod,
            "dev": dev,
            "count": required_count_sessions,
            "stats_prod_before": stats_prod_before,
            "stats_dev_before": stats_dev_before,
            "stats_after_prod": stats_after_prod,
            "stats_dev_after": stats_dev_after,
        }
        message = TelegramMessages().migrate_success(**data)
        await TelegramAPI(TELEGRAM_TOKEN_BOT, TELEGRAM_CHAT_ID).send(message)

    def is_unsolvable_migration(self, collection):
        return (
            False
            if collection not in self.next_use_migration_state
            else self.next_use_migration_state[collection] > datetime.now()
        )
