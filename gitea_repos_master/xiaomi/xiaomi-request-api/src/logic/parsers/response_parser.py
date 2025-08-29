"""
Обрабатывает ответы от сайта Xiaomi.
"""

import json
import logging
from json import JSONDecodeError
from urllib.parse import parse_qs, urlparse

import pydash
from isphere_exceptions.source import SourceError, SourceParseError
from isphere_exceptions.success import NoDataEvent
from requests import Response
from worker_classes.utils import short

from src.logic.xiaomi.exceptions import SessionCaptchaDecodeWarning


class XiaomiResponseParser:
    @staticmethod
    def is_captcha_accepted(json_response: dict) -> bool:
        """Возвращает булевый результат принята капча сайтом (решена правильно) или нет.

        :param json_response: Ответ сайта.
        :return: Результат решения капчи.
        """
        result = pydash.get(json_response, "data.result")
        token = pydash.get(json_response, "data.token")
        if result and token:
            return True

        raise SessionCaptchaDecodeWarning()

    @staticmethod
    def result_response(response: Response) -> dict:
        """Обрабатывает ответ от сайта xiaomi с результатами поиска.
        Возвращает словарь {"result": "Найден", "result_code": "FOUND"}
        или возбуждает исключения NoDataEvent, SourceError, SourceParseError.

        :param response: Ответ сайта.
        :return: Словарь {"result": "Найден", "result_code": "FOUND"}.
        """
        json_response = {}
        try:
            text = XiaomiResponseParser._clean_response(response.text)
            json_response = json.loads(text)
        except Exception as e:
            SourceError(e)

        code = pydash.get(json_response, "code")
        result = pydash.get(json_response, "result")

        if code == 0 and result == "ok":
            return {"result": "Найден", "result_code": "FOUND"}

        if code == 20003 and result == "error":
            raise NoDataEvent()

        logging.warning(f'Xiaomi unknown response to parse: "{json_response}"')
        raise SourceParseError()

    @staticmethod
    def get_e_query_param(response: Response) -> str:
        """Возвращает "e" параметр из URL запроса, который содержится в теле ответа сайта.
        Если параметр не найден - возбуждает исключение SourceError.

        :param response: Ответ сайта xiaomi.
        :return: Параметр "e".
        """
        try:
            json_response = response.json()
        except JSONDecodeError as e:
            logging.warning(f"Main info JSONDecodeError: {short(e)}")
            raise SourceError()

        url = pydash.get(json_response, "data.url")
        if not url:
            raise SourceError("Main info URL is empty")

        parsed_url = urlparse(url)
        query = parse_qs(parsed_url.query)
        if e_param := pydash.get(query, "e.0"):
            return e_param
        raise SourceError('Parameter "e" in main info is empty')

    @staticmethod
    def _clean_response(resp_text: str) -> str:
        """Очищает ответ сайта xiaomi от лишних символов, которые мешают
        преобразованию ответа в JSON формат.
        Если в начале переданной строки присутствует текст &&&START&&&,
        то удаляет его и возвращает результат.
        Если текста нет в начале - возвращает строку без изменений.

        :param resp_text: Тело ответа сайта.
        :return: Строка без текста &&&START&&& в начале.
        """
        if resp_text.startswith("&&&START&&&"):
            return resp_text.replace("&&&START&&&", "")
        return resp_text
