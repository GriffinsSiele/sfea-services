"""
Модуль содержит интерфейс для работы с парсером ответов от сайта.
"""

from abc import ABC, abstractmethod

from requests import Response


class AbstractPageParser(ABC):
    """
    Интерфейс для работы с парсером ответов от сайта.
    """

    @abstractmethod
    def parse(self, response: Response) -> dict:
        """Запускает парсинг ответа.

        :param response: Экземпляр класса `requests.Response`.
        :response: Словарь с результатами парсинга.
        """
        pass
