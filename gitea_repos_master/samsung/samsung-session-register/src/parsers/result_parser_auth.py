from isphere_exceptions.source import SourceError
from pydash import get
from requests import Response

from src.interfaces.abstract_result_parser import AbstractResultParser
from src.logger.context_logger import logging
from src.utils import ExtStr


class SamsungSearchResultParserAuth(AbstractResultParser):
    """Обрабатывает ответ сайта Samsung, возвращает словарь с информацией по использованной сессии.

    Example:
    -------
    ``parse(response) -> {"status": "blocked"}``
    ``parse(response) -> {"status": "success"}``
    """

    ok_definition = "status"
    found_value = "valid"
    not_found_value = "invalid"

    limit_error_definition = "error.code"
    limit_error_values = ("SessionLimit", "SessionLimitSameId")

    blocked_error_definition = "error"
    blocked_error_value = "Too Many Requests"

    def parse(self, response: Response) -> dict:
        """Обработка ответа сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь {"status": "blocked"} или {"status": "success"},
            в случае ошибок возбуждает исключение или SourceError.
        """

        try:
            response_js = response.json()
        except Exception:
            raise SourceError(ExtStr(response.text).short())

        if response.status_code == 200:
            if value := get(response_js, self.ok_definition):
                if value == self.not_found_value:
                    return {"status": "success"}
                if value == self.found_value:
                    return {"status": "success"}

        if response.status_code > 400:
            if get(response_js, self.limit_error_definition) in self.limit_error_values:
                # истекло время жизни сессии или ошибка на стороне источника
                return {"status": "blocked"}

            if (
                get(response_js, self.blocked_error_definition)
                == self.blocked_error_value
            ):
                # Сообщение от сайта "Слишком много попыток входа за короткое время.
                # Перед повторной попыткой входа в систему подождите 30 минут."
                logging.warning("Handler is blocked")
                return {"status": "blocked"}

        raise SourceError(ExtStr(response.text).short())
