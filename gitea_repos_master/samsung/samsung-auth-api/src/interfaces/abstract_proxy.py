"""
Модуль содержит интерфейс для работы с сервисом прокси.
"""

from abc import ABC, abstractmethod


class AbstractProxy(ABC):
    """
    Интерфейс для работы с сервисом прокси.
    """

    @abstractmethod
    async def get_proxy(self, proxy_id: str | None = None) -> dict | None:
        """Возвращает прокси по переданному ID.

        :param proxy_id: ID прокси.
        :return: Прокси.
        """
        pass
