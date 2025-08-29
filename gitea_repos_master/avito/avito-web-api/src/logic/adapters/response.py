from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionLimitError
from isphere_exceptions.source import SourceDown, SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get


class LimitError(SessionLimitError):
    log_level = "warning"


class AvitoProxyBlocked(ProxyBlocked):
    log_level = "warning"


class ResponseAdapter:
    @staticmethod
    def cast(response):
        error_message = get(response, "result.userDialog.message", "")
        response_message = get(response, "result.message", "")
        password_message = get(response, "result.messages.password", "")
        login_message = get(response, "result.messages.login", "")
        internal_error_message = get(response, "error.message", "")

        if (
            "номер мобильного телефона" in login_message
            or "корректный email" in login_message
            or "корректный телефон" in login_message
            or "не должна превышать" in login_message
        ):
            raise SourceIncorrectDataDetected("Входные данные некорректные")

        if (
            "Try again later" in response_message
            or "Internal server error" in internal_error_message
        ):
            raise SourceDown()

        if (
            "Неправильная почта" in error_message
            or "Телефон не привязан к профилю" in error_message
            or "Почта не\xa0привязана к\xa0профилю" in error_message
            or "Почта не привязана к профилю" in error_message
        ):
            raise NoDataEvent("User not registered")

        if (
            "лимит попыток авторизации" in response_message
            or "Неправильный пароль" in error_message
            or "Неправильный пароль" in password_message
            or "Установите новый пароль" in error_message
            or "Похоже, его пытались" in error_message
        ):
            return [{"Result": "Найден", "ResultCode": "FOUND"}]

        raise UnknownError(f"Unknown response: {response}")
