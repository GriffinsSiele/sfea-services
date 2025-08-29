"""
Модуль содержит код обработки ответов сайта Samsung

Возможные ответы:
Найден {"status":"valid","isNoPassword":false}, status code: 200

Не найден {"status":"invalid"}, status code: 200

Превышен лимит по сессии
{"error":{"code":"SessionLimit","message":"Too many request"}}, status code: 403 или 429
Превышен лимит по сессии для одного ID
{"error":{"code":"SessionLimitSameId","message":"Too many request with same input"}}, status code: 429

Заблокирован обработчик {
    "timestamp":"2024-07-30T18:17:04.648+00:00", "path":"/api/v1/signin/auths/accounts",
    "status":429,"error":"Too Many Requests","requestId":"504cac4b-1223543",
    "message":"{\"code\":\"TooManyRequests\",\"message\":\"Too many requests\"}"
}, status code: 403 или 429
"""

import logging

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent
from pydash import get
from requests import Response

from src.exceptions.session import SessionBlockedInfo
from src.logic.parsers.result_parser_common import ResultParserCommon
from src.utils import ExtStr


class SamsungSearchResultParserAuth(ResultParserCommon):
    """Обрабатывает ответ сайта Samsung для обработчика auth"""

    ok_definition = "status"
    found_value = "valid"
    not_found_value = "invalid"

    limit_error_definition = "error.code"
    limit_error_values = ("SessionLimit", "SessionLimitSameId")

    blocked_error_definition = "error"
    blocked_error_value = "Too Many Requests"

    def parse_response_json(
        self, response: Response, response_json: dict, *args, **kwargs
    ) -> dict:
        """Обработка ответа сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :param response_json: Тело ответа сайта в формате dict.
        :return: Словарь {"result": "Найден", "result_code": "FOUND"} или исключение NoDataEvent,
            в случае ошибок возбуждает исключение SourceError.
        """
        if response.status_code == 200:
            if value := get(response_json, self.ok_definition):
                if value == self.not_found_value:
                    raise NoDataEvent()
                if value == self.found_value:
                    return {"result": "Найден", "result_code": "FOUND"}

        if response.status_code > 400:
            if get(response_json, self.limit_error_definition) in self.limit_error_values:
                raise SessionBlockedInfo(
                    "Превышено количество запросов для данной сессии"
                )

            if (
                get(response_json, self.blocked_error_definition)
                == self.blocked_error_value
            ):
                # Сообщение от сайта "Слишком много попыток входа за короткое время.
                # Перед повторной попыткой входа в систему подождите 30 минут."
                logging.warning("Handler is blocked")
                raise SessionBlockedInfo("Обработчик заблокирован")

        raise SourceError(ExtStr(response.text).short_html())
