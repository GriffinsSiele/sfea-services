import logging

from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.source import SourceDown
from isphere_exceptions.worker import UnknownError

from src.logic.adapters.response import PapaJohnsSessionLocked


class PapaJohnsSourceDown(SourceDown):
    log_level = "warning"


class PapaJohnsSessionBlocked(SessionBlocked):
    log_level = "warning"


class ResponseValidation:
    @staticmethod
    def validate_response(response):
        short_text = response.text[:200].replace("\n", "")

        if "decrypt.setPrivateKey" in response.text:
            logging.warning(f"Variti error. Response: {short_text}")
            raise PapaJohnsSessionBlocked("Variti error")
        if "502 Bad Gateway" in response.text:
            raise PapaJohnsSourceDown()
        if "<title>" in response.text:
            raise PapaJohnsSessionLocked("Variti error")

        try:
            response = response.json()
        except Exception as e:
            raise UnknownError(message=f"Error in parse json: {e}. Text: {short_text}")

        logging.info(f"Response is valid: {short_text}")
        return response
