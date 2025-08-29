"""
Модуль содержит код обработки ответа сайта samsung

Примеры ответов сайта:
 Истекло время жизни сессии
 Response status code: 403
 Response text: <!doctype html><html lang="en"><head><title>HTTP Status 403 – Forbidden</title><style
 type="text/css">body {font-family:Tahoma,Arial,sans-serif;} h1, h2, h3, b {color:white;background-color:#525D76;}
 h1 {font-size:22px;} h2 {font-size:16px;} h3 {font-size:14px;} p {font-size:12px;} a {color:black;} .
 line {height:1px;background-color:#525D76;border:none;}</style></head><body><h1>HTTP
 Status 403 – Forbidden</h1></body></html>

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
from pydash import get
from requests import Response

from src.interfaces.abstract_result_parser import AbstractResultParser
from src.utils import ExtStr


class SamsungSearchResultParserName(AbstractResultParser):
    """Обрабатывает ответ сайта Samsung для пролонгатора name,
    возвращает словарь с информацией по использованной сессии.

    Example:
    -------
    ``parse(response) -> {"status": "blocked"}``
    ``parse(response) -> {"status": "success"}``
    """

    found_definition_first = "SUCCESS"
    not_found_definition_first = "FAILED"
    not_found_definition_second = "/accounts/v1/MBR/findIdWithRecoveryFailed"

    session_blocked_definition_first = "EXCEED_LIMIT"
    session_blocked_definition_second = "/accounts/v1/MBR/findIdExceedLimit"

    def parse(self, response: Response) -> dict:
        """Обработка ответа сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь {"status": "blocked"} или {"status": "success"},
            в случае ошибок возбуждает исключение или SourceError.
        """
        if response.status_code == 403 or response.status_code > 500:
            # истекло время жизни сессии или ошибка на стороне источника
            return {"status": "blocked"}

        try:
            response_js = response.json()
        except Exception:
            raise SourceError(ExtStr(response.text).short())

        if response.status_code == 200:
            first_definition = get(response_js, "rtnCd")
            second_definition = get(response_js, "nextURL")

            if (
                first_definition == self.not_found_definition_first
                and second_definition == self.not_found_definition_second
            ):
                # пользователь не найден, но сессия отработала
                return {"status": "success"}

            if first_definition == self.found_definition_first and bool(
                second_definition
            ):
                # пользователь найден, сессия отработала
                return {"status": "success"}

            if (
                first_definition == self.session_blocked_definition_first
                and second_definition == self.session_blocked_definition_second
            ):
                # сессия заблокирована по количеству использований
                return {"status": "blocked"}

        raise SourceError(ExtStr(response.text).short())
