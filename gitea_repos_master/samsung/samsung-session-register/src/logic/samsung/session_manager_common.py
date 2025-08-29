from typing import Type

from isphere_exceptions.worker import InternalWorkerError
from pydash import get

from src.config.app import ConfigApp
from src.config.settings import MONGO_DB, MONGO_URL
from src.interfaces.session_maker import AbstractSessionMaker
from src.logger.context_logger import logging
from src.logic.handlers import PlaceholderSessions, RecoverySessions, UnnecessarySessions
from src.logic.handlers.prolong.prolong_handler_common import SessionProlong
from src.logic.session_storage import SessionStorage
from src.logic.session_storage.filter_constructor_common import FilterConstructor
from src.utils import ExtStr


class SamsungSearchManagerCommon:
    """Управляет сессиями"""

    mongo_collection: str

    unnecessary_sessions_handler = UnnecessarySessions
    recovery_sessions_handler = RecoverySessions
    placeholder_sessions_handler = PlaceholderSessions
    session_storage_cls = SessionStorage

    count_session_target: int
    session_maker_cls: Type[AbstractSessionMaker]
    filter_constructor: Type[FilterConstructor]
    prolong_sessions_handler: Type[SessionProlong]

    @property
    def session_storage(self) -> SessionStorage:
        if not self._session_storage:
            raise InternalWorkerError("Не установлено подключение к хранилищу сессий")
        return self._session_storage

    @session_storage.setter
    def session_storage(self, session_storage_inst: SessionStorage) -> None:
        self._session_storage = session_storage_inst

    async def stop(self) -> None:
        """Останавливает процесс обработки сессий.
        Отключается от БД

        :return: None
        """
        if self.session_storage:
            await self.session_storage.close()

    async def run(self) -> None:
        """Запускает процесс обработки сессий.

        :return: None
        """
        errors = 0
        max_errors = round(self.count_session_target * ConfigApp.MAX_ERRORS_PERCENT / 100)
        self.session_storage = await self.session_storage_cls(
            filter_constructor=self.filter_constructor,
            mongo_url=MONGO_URL,
            db=MONGO_DB,
            collection=self.mongo_collection,
            max_allowed_reconnect=5,
        ).connect()
        try:
            current_state = await self.get_current_state()
        except Exception as e:
            logging.error(ExtStr(e).inline())
            return None

        logging.info(
            f"Current sessions status {current_state}, target {self.count_session_target} active not outdated sessions."
        )

        prolong = get(current_state, "count_prolong", 0)
        if prolong:
            errors += await self._prolong_sessions(prolong)

        count_total = get(current_state, "count_total", -1)
        if count_total != -1 and count_total < self.count_session_target:
            errors += await self._add_new_sessions(
                self.count_session_target - count_total
            )

        if count_total and count_total > self.count_session_target:
            errors += await self._delete_unnecessary_sessions(
                count_total - self.count_session_target
            )

        blocked = get(current_state, "count_blocked") + get(
            current_state, "count_outdated"
        )
        if blocked:
            errors += await self._recover_blocked_sessions()

        current_state = await self.get_current_state()
        logging.info(
            f"Updated session status {current_state}, target {self.count_session_target} active not outdated sessions."
        )

        final_message = f"Number of errors in the process: {errors}"
        if errors >= max_errors:
            logging.error(final_message)
            return None
        logging.info(final_message)

    async def _add_new_sessions(self, count: int) -> int:
        """Добавляет недостающие сессии

        :param count: Количество сессий для добавления
        :return: Количество ошибок возникших при добавлении сессий
        """
        return await self.placeholder_sessions_handler(
            self.session_storage, self.session_maker_cls
        ).add(count, self.count_session_target)

    async def _delete_unnecessary_sessions(self, count: int) -> int:
        """Удаляет лишние сессии.

        :param count: Количество сессий для удаления
        :return: Количество ошибок возникших при удалении сессий
        """
        return await self.unnecessary_sessions_handler(self.session_storage).delete(count)

    async def _prolong_sessions(self, count_sessions: int) -> int:
        """Опрашивает сессии с целью продлить их работоспособность.

        :return: Количество ошибок возникших при опросе сессий
        """
        return await self.prolong_sessions_handler(self.session_storage).prolong(
            count_sessions
        )

    async def _recover_blocked_sessions(self) -> int:
        """Обновляет заблокированные сессии.

        :return: Количество ошибок возникших при обновлении заблокированных сессий
        """
        return await self.recovery_sessions_handler(
            self.session_storage, self.session_maker_cls
        ).recovery()

    async def get_current_state(self) -> dict:
        """Возвращает текущее состояние хранилища сессий
        (Сколько сессий активно, заблокировано, временно недоступно, устарело, всего).

        :return: Статистика по сессиям.
        """
        current_state = await self.session_storage.aggregate_statistics()
        current_state["count_outdated"] = (
            await self.session_storage.count_active_outdated_sessions()
        )
        current_state["count_outdated"] = (
            await self.session_storage.count_active_outdated_sessions()
        )
        current_state["count_prolong"] = (
            await self.session_storage.count_active_sessions_to_prolong()
        )

        return current_state
