from typing import Type

from pydash import get

from src.interfaces.abstract_result_parser import AbstractResultParser
from src.logger.context_logger import logging
from src.logic.session_storage import SessionStorage
from src.utils import ExtStr

from .prolong_common import Prolong


class SessionProlong:
    """Опрашивает сессии с целью продлить их работоспособность"""

    prolong_cls: Type[Prolong]
    result_parser_cls: Type[AbstractResultParser]

    def __init__(self, session_storage: SessionStorage) -> None:
        self.session_storage = session_storage
        self.handler = self.prolong_cls(session_storage)
        self.response_parser = self.result_parser_cls()

    async def prolong(self, count_sessions: int) -> int:
        errors = 0
        for _ in range(count_sessions):
            session = await self.get_session_to_prolong()
            if not session:
                break
            session_id = get(session, "_id")
            logging.info(f'"Prolonging the session with ID: {session_id}"')
            try:
                await self.handler.prolong(session, self.response_parser)
            except Exception as e:
                logging.warning(
                    f"Prolonging session with ID: {session_id} failed {ExtStr(e).inline()}"
                )
                errors += 1
            else:
                logging.info(f"Prolonging of session with ID: {session_id} succeeded")

        return errors

    async def get_session_to_prolong(self) -> dict | None:
        try:
            return await self.session_storage.get_active_session_to_prolong()
        except Exception as e:
            logging.info(f"Failed to get session: {ExtStr(e).inline()}")
            return None
