from typing import Type

from isphere_exceptions.proxy import ProxyTemporaryUnavailable
from isphere_exceptions.session import SessionEmpty, SessionError
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.worker import InternalWorkerError, InternalWorkerTimeout
from mongo_client.client import MongoSessions
from pydash import get
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.timing import TimeoutHandler

from src.config import ConfigApp
from src.fastapi.schemas import SamsungSearchDataAuth, SamsungSearchDataPerson
from src.interfaces import AbstractSamsung
from src.logger.context_logger import logging
from src.logic.samsung.exception_handler import exception_handler
from src.proxy import Proxy
from src.utils import now
from src.utils.utils import informer


class SamsungSearchManagerCommon(SearchManager):
    """
    Осуществляет поиск аккаунта пользователя.
    Подготавливает сессию и прокси для поиска.
    """

    proxy_service = Proxy
    samsung: Type[AbstractSamsung]

    def __init__(self, session_storage: MongoSessions, *args, **kwargs) -> None:
        """Метод инициализации класса.

        :param session_storage: Подключение к бозе данных, которая хранит сессии.
        :param args: Необязательные позиционные аргументы.
        :param kwargs: Необязательные ключевые аргументы.
        :return: None.
        """
        super().__init__(*args, **kwargs)
        self.session_storage = session_storage
        self.session: dict | None = None
        self.proxy: dict | None = None

    async def _prepare(self) -> None:
        """Подготавливает сессию и прокси для поиска.

        :return: None.
        """
        await self._get_session()
        await self._get_proxy()

    async def _search(
        self, data: SamsungSearchDataAuth | SamsungSearchDataPerson, *args, **kwargs
    ) -> dict:
        """Запускает поиск аккаунта.

        :param data: Данные для поиска.
        :param args: Необязательные позиционные аргументы.
        :param kwargs: Необязательные ключевые аргументы.
        :return: Результат поиска.
        """
        search_result = {}
        try:
            if not data.payload:
                raise SourceIncorrectDataDetected()

            if data.timeout and data.starttime and data.timeout + data.starttime < now():
                raise InternalWorkerTimeout()

            if not self.session or not self.proxy:
                raise InternalWorkerError()

            handler = TimeoutHandler(timeout=ConfigApp.TASK_TIMEOUT)
            samsung = self.samsung(self.session, self.proxy)
            search_result = await handler.execute(
                samsung.search, data.payload, *args, **kwargs
            )

        except Exception as e:
            return await exception_handler.call(
                e,
                logger=logging,
                session_storage=self.session_storage,
                session=self.session,
                *args,
                **kwargs,
            )
        else:
            return await exception_handler.normal(
                logger=logging,
                search_result=search_result,
                session_storage=self.session_storage,
                session=self.session,
                *args,
                **kwargs,
            )

    @informer(1, "Getting session")
    async def _get_session(self) -> None:
        """Получает сохраненную ранее в хранилище сессию.

        :return: None
        """
        self.session = await self.session_storage.get_session()
        if not self.session:
            raise SessionEmpty()

    @informer(2, "Getting proxy")
    async def _get_proxy(self) -> None:
        """Получает прокси на основе данных из сессии (proxy_id).

        :return: None
        """
        proxy_id = get(self.session, "session.proxy_id")
        if not proxy_id:
            logging.warning("SessionError: Session does not contain proxy id")
            raise SessionError()

        self.proxy = await self.proxy_service().get_proxy(proxy_id)
        if not self.proxy:
            raise ProxyTemporaryUnavailable()
