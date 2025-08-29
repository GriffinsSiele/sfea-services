from typing import Type

from pydash import get
from worker_classes.thread.timing import timing

from src.interfaces.session_maker import AbstractSessionMaker
from src.logger.context_logger import logging
from src.logic.adapters import SessionAdapter
from src.logic.handlers.session_maker_mixin import SessionMakerMixin
from src.logic.session_storage import SessionStorage
from src.utils import ExtStr


class RecoverySessions(SessionMakerMixin):
    """Обновляет заблокированные сессии"""

    session_adapter_cls = SessionAdapter

    def __init__(
        self, session_storage: SessionStorage, session_maker: Type[AbstractSessionMaker]
    ) -> None:
        """Конструктор класса.

        :param session_storage: Хранилище сессий (SessionStorage).
        :param session_maker: Генератор сессий (AbstractSessionMaker).
        :return: None.
        """
        self.session_storage = session_storage
        self.session_maker_cls = session_maker

    @timing("Recovery sessions spent time")
    async def recovery(self) -> int:
        """Обновляет заблокированные и устаревшие сессии.

        :return: Количество ошибок (не обновленных сессий).
        """
        errors = 0
        blocked_sessions = await self.session_storage.get_blocked_sessions()
        outdated_sessions = await self.session_storage.get_active_outdated_sessions()
        blocked_sessions += outdated_sessions
        if not blocked_sessions:
            logging.info("Failed to load blocked session IDs")
            return 1

        total_session_numbers = len(blocked_sessions)
        logging.info(
            f"Recovering {total_session_numbers} blocked or outdated sessions..."
        )

        for current_session_number in range(total_session_numbers):
            session = await self.make_session()
            if not session:
                errors += 1
                continue

            session_data = self.session_adapter_cls.cast_for_update(session)

            try:
                refreshed_blocked_sessions = (
                    await self.session_storage.get_active_outdated_sessions()
                )
                if not refreshed_blocked_sessions:
                    refreshed_blocked_sessions = (
                        await self.session_storage.get_blocked_sessions()
                    )

                if not refreshed_blocked_sessions:
                    logging.info(f"All sessions recovered. Left earlier than necessary.")
                    break

                refreshed_blocked_session = get(refreshed_blocked_sessions, "0", {})
                if not refreshed_blocked_session:
                    logging.info("Failed to load refreshed blocked session")
                    continue

                await self.session_storage.session_update(
                    session=refreshed_blocked_session, payload=session_data
                )
                logging.info(
                    f"Recovered session {current_session_number + 1} of {total_session_numbers}, "
                    f"session ID:{get(refreshed_blocked_session, '_id')}"
                )

                if len(refreshed_blocked_sessions) <= 1:
                    # последняя сессия обновлена, выходим
                    break

            except Exception as e:
                logging.error(f"Failed to save session to storage: {ExtStr(e).inline()}")
                errors += 1

        return errors
