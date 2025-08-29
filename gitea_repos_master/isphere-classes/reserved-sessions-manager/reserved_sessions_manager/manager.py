import logging
import os
from datetime import datetime, timedelta

from reserved_sessions_manager.telegram import TelegramMonitoring


class ReservedSessionManager:
    CRITICAL_DEV_COUNT_SESSION = 3
    DEV_COLLECTION_SUFFIX = "dev"
    PROD_COLLECTION_SUFFIX = "prod"

    def __init__(
        self,
        telegram_token,
        telegram_chat_id,
        collection,
        dev=None,
        prod=None,
        critical=None,
    ):
        self.telegram = TelegramMonitoring(telegram_token, telegram_chat_id)
        self.mongo_collection = collection

        self.DEV_COLLECTION_SUFFIX = dev if dev else self.DEV_COLLECTION_SUFFIX
        self.PROD_COLLECTION_SUFFIX = prod if prod else self.PROD_COLLECTION_SUFFIX
        self.CRITICAL_DEV_COUNT_SESSION = (
            critical if critical else self.CRITICAL_DEV_COUNT_SESSION
        )

        self.next_use_migration = None

    def solve_lack_sessions(self, mongo):
        if not self.is_master:
            return False, 10

        if self.is_unsolvable_migration:
            logging.info("Lack of sessions cant be solved by migration now")
            return True, 0

        logging.info("Master thread solving session lack")
        self._migrate(mongo)
        return False, 0

    def _migrate(self, mongo):
        dev_collection = (
            f"{self.mongo_collection}-{ReservedSessionManager.DEV_COLLECTION_SUFFIX}"
        )
        prod_collection = (
            f"{self.mongo_collection}-{ReservedSessionManager.PROD_COLLECTION_SUFFIX}"
        )
        message = f"Обнаружен недостаток сессий в базе {prod_collection}.\n\n"

        logging.info(f"Staring migration from {dev_collection}")
        mongo.switch_collection(dev_collection)
        count_active = mongo.count_active()

        if count_active < ReservedSessionManager.CRITICAL_DEV_COUNT_SESSION:
            logging.info("Not enough session for migration")
            message += (
                f'В таблице "{dev_collection}" недостаточно сессий '
                f"для пополнения - {count_active}.\nПожалуйста, пополните "
                f'"{dev_collection}" и "{prod_collection}" сессионными данными.\n'
                f"На данный момент оставлен только "
                f"1 обработчик на выдачу ошибок недостатка сессий, остальные "
                f"переведены в спящий режим до момента пополнения сессий."
            )
            mongo.switch_collection(prod_collection)
            self.next_use_migration = datetime.now() + timedelta(hours=12)
            self.telegram.send(message)
            return

        required_count_sessions = int(count_active * 0.65)

        sessions = []
        for _ in range(required_count_sessions):
            session = mongo.get_session()
            logging.info(f"Extracting session: {session}")
            sessions.append(session)
            mongo.delete_session(session)

        mongo.switch_collection(prod_collection)

        count_active_before = mongo.count_active()
        for session in sessions:
            mongo.add(session)
        count_active_after = mongo.count_active()

        message += (
            f'Выполнена миграция сессий из "{dev_collection}" в '
            f'"{prod_collection}" в размере {len(sessions)} записей.\n'
            f"Количество активных сессий до миграции: {count_active_before}.\n"
            f"Количество активных сессий после миграции: {count_active_after}.\n"
            f"Не забудьте пополнить количество резервных сессий."
        )
        self.telegram.send(message)

    @property
    def is_master(self):
        return os.uname().nodename.endswith("-0")

    @property
    def is_unsolvable_migration(self):
        return (
            False
            if not self.next_use_migration
            else self.next_use_migration > datetime.now()
        )
