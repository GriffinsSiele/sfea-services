from typing import Callable, Type

from src.interfaces.session_maker import AbstractSessionMaker
from src.logger.context_logger import logging
from src.logic.randomizer import random_fake_email, random_fake_person
from src.logic.samsung.session_maker_person import SessionMakerPerson
from src.utils import ExtStr


class SessionMakerMixin:
    """Миксин, содержит функционал для получения сессии"""

    session_maker_cls: Type[AbstractSessionMaker]
    search_data_maker: Callable

    async def make_session(self) -> dict | None:
        """Генерирует сессию

        :return: Сессия
        """
        session_maker = None
        search_data: str | dict
        try:
            session_maker = await self.session_maker_cls().prepare()
            if self.session_maker_cls is SessionMakerPerson:
                search_data = random_fake_person()
            else:
                search_data = random_fake_email()
            logging.info(f"Search data: {search_data}")
            return await session_maker.make(search_data)
        except Exception as e:
            logging.warning(
                f"Failed to get session from samsung site: {ExtStr(e).inline()}"
            )
        finally:
            del session_maker

        return None
