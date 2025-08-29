"""
Модуль содержит код обработки ответа сайта samsung

Примеры ответов сайта:
 Истекло время жизни сессии
 Response status code: 403
 Response text: <!doctype html><html lang="en"><head><title>HTTP Status 403 – Forbidden</title><style ...

 Превышено количество запросов для данной сессии
 Response status code: 200
 Response text: {"rtnCd":"EXCEED","nextURL":"/accounts/v1/MBR/findIdExceedLimit"}

 Пользователь не найден
 Response status code: 200
 Response text: {"rtnCd":"SUCCESS","rtnMap":{"userLoginIDVOList":[]}}

 Пользователь найден
 Response status code: 200
 Response text: {"rtnCd":"SUCCESS","rtnMap":{"userLoginIDVOList":[{
                "loginID": "***********@gmail.com",
                "loginIDTypeCode": "003",
                "userStatusCode": "1",
            }, ...}}
"""

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent
from pydash import get
from requests import Response

from src.exceptions.session import SessionBlockedInfo
from src.logic.parsers.result_parser_common import ResultParserCommon
from src.utils import ExtStr


class SamsungSearchResultParserPerson(ResultParserCommon):
    """Обрабатывает ответ сайта Samsung для обработчика person"""

    ok_definition = "SUCCESS"
    first_session_blocked_definition = "EXCEED"
    second_session_blocked_definition = "/accounts/v1/MBR/findIdExceedLimit"

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
            second_definition = get(response_json, "rtnMap.userLoginIDVOList")
            second_definition_blocked = get(response_json, "nextURL")

            if first_definition == self.ok_definition and not bool(second_definition):
                raise NoDataEvent()

            if first_definition == self.ok_definition and bool(second_definition):
                return {
                    "result": "Найден",
                    "result_code": "FOUND",
                    "emails": self._get_emails(response_json),
                }

            if (
                first_definition == self.first_session_blocked_definition
                and second_definition_blocked == self.second_session_blocked_definition
            ):
                raise SessionBlockedInfo(
                    "Превышено количество запросов для данной сессии"
                )

        raise SourceError(ExtStr(response.text).short_html())

    @staticmethod
    def _get_emails(response_dict: dict) -> list:
        """Возвращает список e-mail пользователя.

        :param response_dict: Тело ответа в формате dict
        :return: Список e-mail
        """
        raw_email_list = get(response_dict, "rtnMap.userLoginIDVOList")
        if not raw_email_list:
            return []

        email_list = []
        for email in raw_email_list:
            email_only = get(email, "loginID")
            if email_only:
                email_list.append(email_only)
        return list(set(email_list))
