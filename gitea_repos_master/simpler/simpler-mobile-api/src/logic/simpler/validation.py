import logging

from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionBlocked, SessionLocked
from isphere_exceptions.worker import UnknownError
from pydash import get


class ResponseValidation:
    @staticmethod
    def validate_response(response):
        try:
            response = response.json()
        except Exception as e:
            raise UnknownError(f"Error in parse json: {e}. Text: {response.text}")

        message = get(response, "message", "")
        if "Invalid signature" in message:
            raise SessionBlocked(message)

        if "rate limit exceeded" in message:
            raise SessionLocked(message)

        if "permission to access" in message:
            raise ProxyBlocked(message)

        result = get(response, "result")

        if not result or not isinstance(result, list):
            raise UnknownError(f"Response body is invalid: {response}")

        logging.info("Response is valid")
        return response
