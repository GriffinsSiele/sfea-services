from typing import Type

from mongo_client.client import MongoSessions

from src.logic.session_storage.filter_constructor_common import FilterConstructor


class SessionStorage(MongoSessions):
    """
    Расширяет функционал класса MongoSessions.
    Добавлены методы get_blocked_sessions, get_active_outdated_sessions и count_active_outdated_sessions.
    """

    def __init__(self, filter_constructor: Type[FilterConstructor], *args, **kwargs):
        """Конструктор класса.

        :param filter_constructor: Определяет класс-конструктор фильтров для поиска сессий.
        """
        self.filter_constructor = filter_constructor()
        super().__init__(*args, **kwargs)

    async def get_blocked_sessions(self) -> list[dict]:
        """Возвращает список заблокированных сессий.

        :return: Список id заблокированных сессий.
        """
        self.projection = {"_id": 1}
        self.default_filter = {self.active: False}
        return await self.get_sessions()

    async def get_active_outdated_sessions(self) -> list[dict]:
        """Возвращает активные сессии которые устарели по времени
        и не будут приняты сайтом при их использовании.
        Время устаревания сессии задается в классе ConfigApp, параметр TIME_SESSION_BECOMES_OLD.

        :return: Список id устаревших сессий.
        """
        self.projection = {"_id": 1}
        self.default_filter = self.filter_constructor.get_outdated_sessions_filter()
        return await self.get_sessions()

    async def get_active_session_to_prolong(self) -> dict:
        """Возвращает активные сессии которые скоро устареют по времени
        и не будут приняты сайтом при их использовании.
        Время устаревания сессии задается в классе ConfigApp, параметр TIME_SESSION_PROLONG.

        :return: Список id устаревших сессий.
        """
        self.projection = {"_id": 1, "count_use": 1, "session": 1}
        self.default_filter = self.filter_constructor.get_prolong_filter()
        return await self.get_session()

    async def count_active_outdated_sessions(self) -> int:
        """Возвращает количество устаревших сессий.
        Время устаревания сессии задается в классе ConfigApp, параметр TIME_SESSION_BECOMES_OLD.

        :return: Количество устаревших сессий.
        """
        return await self.count_documents(
            filter=self.filter_constructor.get_outdated_sessions_filter()
        )

    async def count_active_sessions_to_prolong(self) -> int:
        """Возвращает количество активных сессий которые скоро устареют по времени
        и не будут приняты сайтом при их использовании.
        Время устаревания сессии задается в классе ConfigApp, параметр TIME_SESSION_PROLONG.

        :return: Количество сессий, которые скоро устареют по времени.
        """
        return await self.count_documents(
            filter=self.filter_constructor.get_prolong_filter()
        )

    async def count_total_sessions(self) -> int:
        """Возвращает количество сессий"""
        return await self.count_documents(filter={})

    def _filter_exclude_lock(self, offset=0) -> dict:
        """Используется в методе "get_session" базового класса и вносит нежелательные для данного приложения изменения,
        с целью упрощения кода был переопределен на возврат пустого словаря.
        """
        return {}
