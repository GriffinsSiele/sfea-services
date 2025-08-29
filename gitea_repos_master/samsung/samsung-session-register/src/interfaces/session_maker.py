from abc import ABC, abstractmethod


class AbstractSessionMaker(ABC):
    """Интерфейс для класса, который генерирует сессии"""

    @abstractmethod
    async def prepare(self) -> "AbstractSessionMaker":
        """Выполняет подготовку браузера для получения сессии.

        :return: SessionMaker
        """
        pass

    @abstractmethod
    async def make(self, search_data: str | dict) -> dict:
        """Генерирует сессию

        :param search_data: Данные для поиска (заведомо не существующий аккаунт)
        :return: Сессия
        """
        pass
