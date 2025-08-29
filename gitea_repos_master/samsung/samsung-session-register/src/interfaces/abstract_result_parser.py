from abc import ABC, abstractmethod

from requests import Response


class AbstractResultParser(ABC):
    """Интерфейс для парсера ответа сайта"""

    @abstractmethod
    def parse(self, response: Response) -> dict:
        """Обрабатывает ответ сайта, возвращает словарь с информацией по использованной сессии.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь {"status": "blocked"} или {"status": "success"},
            в случае ошибок возбуждает исключение или SourceError.
        """
        pass
