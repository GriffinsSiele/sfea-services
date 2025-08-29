"""
Модуль содержит интерфейс для работы с сервисом прокси.
"""

from abc import ABC, abstractmethod


class AbstractProxy(ABC):
    """
    Интерфейс для работы с сервисом прокси.
    Используемый сервис прокси должен возвращать словарь с ключом 'http' или 'https',
    как показано в примере ниже.

    Example:
    -------
    ``get_proxy() -> '{'http': 'http://...', 'https': 'http://...', ...}'``
    """

    @abstractmethod
    async def get_proxy(self) -> dict | None:
        """Возвращает прокси.

        :return: Словарь с ключами 'http' или 'https'.
        """
        pass
