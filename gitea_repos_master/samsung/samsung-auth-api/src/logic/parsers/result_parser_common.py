"""
Модуль содержит код обработки ответа сайта Samsung
"""

from isphere_exceptions.source import SourceError
from requests import Response

from src.exceptions.session import SessionBlockedInfo
from src.interfaces import AbstractResultParser
from src.utils import ExtStr


class ResultParserCommon(AbstractResultParser):
    """Обрабатывает ответ сайта Samsung. Содержит общую логику для всех обработчиков."""

    def parse(self, response: Response, *args, **kwargs) -> dict:
        """Обработка ответа сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь с результатами поиска или исключение NoDataEvent,
            в случае ошибок возбуждает исключение SessionBlockedInfo или SourceError.
        """
        if response.status_code == 403:  # истекло время жизни сессии
            raise SessionBlockedInfo("Истекло время жизни сессии")

        if response.status_code > 500:
            raise SourceError()

        response_js = self.get_response_json(response)
        return self.parse_response_json(response, response_js, **kwargs)

    def parse_response_json(
        self, response: Response, response_json: dict, *args, **kwargs
    ) -> dict:
        """Обработка тела ответа сайта. Уникально для каждого обработчика.

        :param response: Ответ сайта в формате requests.Response.
        :param response_json: Тело ответа сайта в формате dict.
        return: Словарь с результатами поиска или исключение NoDataEvent,
            в случае ошибок должно возбудить исключение SourceError.
        """
        raise NotImplemented

    @staticmethod
    def get_response_json(response: Response) -> dict:
        """Извлекает JSON от HTTP ответа или возбуждает исключение SourceError

        :return: Тело ответа сайта в формате dict
        """
        try:
            return response.json()
        except Exception:
            raise SourceError(ExtStr(response.text).short_html())
