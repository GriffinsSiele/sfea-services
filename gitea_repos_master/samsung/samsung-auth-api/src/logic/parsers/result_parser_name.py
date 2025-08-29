"""
Модуль содержит код обработки ответа сайта samsung

Примеры ответов сайта:
 Истекло время жизни сессии
 Response status code: 403
 Response text: <!doctype html><html lang="en"><head><title>HTTP Status 403 – Forbidden</title><style ...

 Превышено количество запросов для данной сессии
 Response status code: 200
 Response text: {"rtnCd":"EXCEED_LIMIT","nextURL":"/accounts/v1/MBR/findIdExceedLimit"}

 Пользователь не найден
 Response status code: 200
 Response text: {"rtnCd":"FAILED","nextURL":"/accounts/v1/MBR/findIdWithRecoveryFailed"}

 Пользователь найден
 Response status code: 200
 Response text: {"rtnCd":"SUCCESS","nextURL":"/accounts/v1/MBR/findIdWithRecoverySmsVerification"}
"""

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent
from pydash import get
from requests import Response

from src.exceptions.session import SessionBlockedInfo
from src.logic.parsers.result_parser_common import ResultParserCommon
from src.utils import ExtStr


class SamsungSearchResultParserName(ResultParserCommon):
    """Обрабатывает ответ сайта Samsung для обработчика name"""

    found_definition_first = "SUCCESS"
    not_found_definition_first = "FAILED"
    not_found_definition_second = "/accounts/v1/MBR/findIdWithRecoveryFailed"

    session_blocked_definition_first = "EXCEED_LIMIT"
    session_blocked_definition_second = "/accounts/v1/MBR/findIdExceedLimit"

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
            first_definition = get(response_json, "rtnCd")
            second_definition = get(response_json, "nextURL")

            if (
                first_definition == self.not_found_definition_first
                and second_definition == self.not_found_definition_second
            ):
                raise NoDataEvent()

            if first_definition == self.found_definition_first and bool(
                second_definition
            ):
                data = kwargs.get("data")
                if not data:
                    return {"result": "Найден", "result_code": "MATCHED"}
                if self.is_email(data):
                    return {
                        "result": "Найден, e-mail соответствует фамилии и имени",
                        "result_code": "MATCHED",
                    }
                return {
                    "result": "Найден, телефон соответствует фамилии и имени",
                    "result_code": "MATCHED",
                }

            if (
                first_definition == self.session_blocked_definition_first
                and second_definition == self.session_blocked_definition_second
            ):
                raise SessionBlockedInfo(
                    "Превышено количество запросов для данной сессии"
                )

        raise SourceError(ExtStr(response.text).short_html())

    @staticmethod
    def is_email(data: str) -> bool:
        return bool("@" in data)
