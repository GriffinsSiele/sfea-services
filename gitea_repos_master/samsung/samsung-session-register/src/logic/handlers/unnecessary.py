from pydash import get

from src.logger.context_logger import logging
from src.logic.session_storage import SessionStorage
from src.utils import ExtStr


class UnnecessarySessions:
    """Удаляет лишние сессии"""

    def __init__(self, session_storage: SessionStorage) -> None:
        self.session_storage = session_storage

    async def delete(self, count: int) -> int:
        """Удаляет лишние сессии.
        Сначала удаляются заблокированные, затем неиспользуемые в данный момент.
        """
        errors = 0
        logging.info(f"Deleting {count} unnecessary sessions...")
        # В первую очередь удаляем заблокированные сессии
        session_for_delete = await self.session_storage.get_blocked_sessions()

        # Если заблокированных сессий недостаточно, то добавляем старые
        # и не используемые в данный момент сессии
        len_session_for_delete = len(session_for_delete)
        if len_session_for_delete < count:
            session_for_delete_alive = (
                await self.session_storage.get_active_outdated_sessions()
            )
            session_for_delete = (
                session_for_delete
                + session_for_delete_alive[: count - len_session_for_delete]
            )

        logging.info(f"ID sessions for delete {session_for_delete}")

        deleted_count = 0
        for session in session_for_delete:
            deleted_count += 1
            try:
                await self.session_storage.session_delete(session)
                logging.info(
                    f"Deleted session {deleted_count} of {count}, "
                    f"session ID:{get(session, '_id')}"
                )
            except Exception as e:
                logging.error(f"Failed to save session to storage: {ExtStr(e).inline()}")
                errors += 1
                continue

        return errors
