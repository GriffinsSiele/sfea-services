from isphere_exceptions.session import SessionLocked
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get


class PapaJohnsSessionLocked(SessionLocked):
    log_level = "warning"


class ResponseAdapter:
    @staticmethod
    def cast(response):
        message = get(response, "message")
        if message == "Пользователь не найден":
            raise NoDataEvent(message=message)

        if message == "Неверный пароль":
            return [{"Result": "Найден", "ResultCode": "FOUND"}]

        if message == "Закончился лимит обращений к серверу":
            raise PapaJohnsSessionLocked(message=message)

        raise UnknownError(message=response)
