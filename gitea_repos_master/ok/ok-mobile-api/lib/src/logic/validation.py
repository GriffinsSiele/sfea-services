import json

from isphere_exceptions.session import (
    SessionBlocked,
    SessionInvalidCredentials,
    SessionLimitError,
    SessionLocked,
    SessionOutdated,
)
from isphere_exceptions.worker import UnknownError
from pydash import get


class ResponseValidation:
    @staticmethod
    async def validate_request(request, return_response_raw=False):
        try:
            response_o = await request.request()
        except Exception as e:
            raise UnknownError(f"Произошла ошибка отправки запроса: {e}")

        try:
            response = response_o.json()
        except Exception:
            raise UnknownError(f"Ответ сервера не json: {response_o.text}")

        error_msg = get(response, "error_msg", "")

        if "Invalid session key" in error_msg or "PARAM_SESSION_EXPIRED" in error_msg:
            raise SessionOutdated("session_key неверный")

        if "INVALID_CREDENTIALS" in error_msg:
            raise SessionInvalidCredentials("Неверный логин/пароль")

        if "Limit Speed" in error_msg:
            raise SessionLimitError(
                "Превышен лимит попыток за сутки, попробуйте через 24 часа"
            )

        if (
            "AUTH_LOGIN : BLOCKED" in error_msg
            or "AUTH_LOGIN_WEB_HUMAN_CHECK" in error_msg
        ):
            raise SessionBlocked(response)

        if "AUTH_LOGIN" in error_msg:
            raise SessionLocked(json.dumps(response))

        if error_msg:
            raise UnknownError(f"В ответе сообщение с ошибкой: {response}")

        if not return_response_raw:
            return response

        return response, response_o
