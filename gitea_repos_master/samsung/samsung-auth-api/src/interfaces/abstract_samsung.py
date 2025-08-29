"""
Модуль содержит интерфейс поиска аккаунта на сайте Samsung.
"""

from abc import ABC, abstractmethod


class AbstractSamsung(ABC):
    """Интерфейс для поиска аккаунта на сайте Samsung."""

    def __init__(self, *args, **kwargs):
        pass

    @abstractmethod
    async def search(self, data: str | dict) -> dict:
        """Запускает поиск аккаунта на сайте Samsung.

        :param data: Проверяемый аккаунт.
        :return: Словарь с результатами проверки (найден или нет).
        """
        pass
