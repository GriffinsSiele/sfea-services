import logging

from isphere_exceptions.worker import UnknownError
from pydash import get

from src.logic.adapters.response import LimitError


class ResponseValidation:
    @staticmethod
    def validate_response(response):
        text = response.text[:200].replace("\n", "")

        if "Доступ ограничен: проблема с IP" in response.text:
            logging.warning(f"Error in response: {text}")
            raise LimitError()

        if "Подождите, идет загрузка" in response.text:
            message = "Временная блокировка сессии из-за детекции на js"
            logging.warning(message)
            raise LimitError(message)

        try:
            response = response.json()
        except Exception as e:
            raise UnknownError(f"Error in parse json: {e}. Text: {text}")

        error_message = get(response, "result.message", "")

        if "адреса временно ограничен" in error_message:
            raise LimitError(error_message)

        logging.info(f"Response is valid: {str(response)[:200]}")
        return response
