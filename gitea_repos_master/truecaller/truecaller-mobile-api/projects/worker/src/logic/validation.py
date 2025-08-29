import logging

from isphere_exceptions.session import SessionBlocked, SessionLocked
from isphere_exceptions.worker import UnknownError
from pydash import get


class ResponseValidation:
    @staticmethod
    async def validate_request(request):
        try:
            response = await request.request()
        except Exception as e:
            raise UnknownError(f"Произошла ошибка отправки запроса: {e}")

        if (
            "too many" in response.text
            or "timeoutSeconds" in response.text
            or response.status_code == 429
        ):
            logging.warning(f"Response: {response.text}")
            raise SessionLocked(f"Обнаружен признак блокировки: {response.status_code}")

        try:
            response = response.json()
        except Exception:
            raise UnknownError(f"Ответ сервера не json: [{response}] {response.text}")

        error_msg = get(response, "message", "")

        if "Unauthorized" in error_msg or "Account suspended" in error_msg:
            raise SessionBlocked(error_msg)

        if error_msg:
            raise UnknownError(f"В ответе сообщение с ошибкой: {response}")

        return response
