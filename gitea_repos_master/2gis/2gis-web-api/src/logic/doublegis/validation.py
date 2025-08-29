import logging

from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.session import (
    SessionCaptchaDetected,
    SessionLimitError,
    SessionLocked,
)
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get


class ResponseValidation:
    @staticmethod
    def validate_response(response):
        if response.status_code >= 300:
            raise UnknownError(
                f"Response with unknown status code: {response.status_code}. Text: {response.text}"
            )

        if "Text: ->" in response.text or "uTlsConn.Handshake() error" in response.text:
            raise ProxyLocked("Go Server error")

        if "decrypt.setPrivateKey" in response.text:
            raise SessionLocked("variti.io not passed")

        try:
            response = response.json()
        except Exception as e:
            raise UnknownError(f"Error in parse json: {e}. Text: {response.text}")

        error_message = get(response, "meta.error.message", "")
        if "Authorization error" in error_message:
            raise SessionLimitError(response)
        if "Results not found" in error_message:
            raise NoDataEvent(error_message)

        response_code = get(response, "meta.code")
        if response_code == 307:
            raise SessionCaptchaDetected(get(response, "meta.location"))

        is_partial = get(response, "result.search_attributes.is_partial", False)
        if is_partial:
            raise NoDataEvent("Не найден")

        logging.info(f"Response is valid: {response}")
        return response
