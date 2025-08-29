from typing import Type

from src.interfaces.session_maker import AbstractSessionMaker
from src.logger.context_logger import logging
from src.logic.adapters import SessionAdapter
from src.logic.handlers.session_maker_mixin import SessionMakerMixin
from src.logic.session_storage import SessionStorage
from src.utils import ExtStr


class PlaceholderSessions(SessionMakerMixin):
    """Добавляет недостающие сессии"""

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

    async def add(self, count: int, count_session_target: int) -> int:
        """Добавляет недостающие сессии. В процессе работы проверяет сколько сессий еще нужно добавить,
        что позволяет работать нескольким экземплярам данного класса параллельно.

        :param count: Предварительное количество сессий для добавления.
        :param count_session_target: Количество сессий, которое необходимо достичь.
        """
        logging.info(f"Adding {count} new sessions...")
        errors = 0
        for i in range(count):
            session = await self.make_session()
            if not session:
                errors += 1
                continue

            adapted_session = self.session_adapter_cls.cast(session)

            try:
                current_count = await self.session_storage.count_total_sessions()
                if current_count >= count_session_target:
                    logging.info(
                        f"All sessions added. Left earlier than necessary, step {i} from {count}"
                    )
                    break

                session_id = await self.session_storage.add(adapted_session)
                logging.info(f"Added session {i + 1} of {count}, session ID:{session_id}")

                if current_count + 1 == count_session_target:
                    # была добавлена последняя недостающая сессия, выходим
                    break
            except Exception as e:
                logging.error(f"Failed to save session to storage: {ExtStr(e).inline()}")
                errors += 1
                continue

        return errors
