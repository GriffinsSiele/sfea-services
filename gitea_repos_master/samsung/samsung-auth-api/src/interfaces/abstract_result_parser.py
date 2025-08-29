"""
Модуль содержит интерфейс парсера ответа сайта Samsung.
"""

from abc import ABC, abstractmethod

from requests import Response


class AbstractResultParser(ABC):
    @abstractmethod
    def parse(self, response: Response, *args, **kwargs) -> dict:
        """Обработка ответа сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь {"result": "Найден", "result_code": "FOUND",
            "user_info: [<дополнительная информация о пользователе>]"}
            или исключение NoDataEvent, в случае ошибок возбуждает исключение SessionBlockedInfo или SourceError.
        """
        pass
