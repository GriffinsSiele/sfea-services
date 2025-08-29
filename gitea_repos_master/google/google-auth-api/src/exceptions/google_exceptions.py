from isphere_exceptions.session import SessionBlocked, SessionCaptchaDecodeError
from isphere_exceptions.source import SourceOperationFailure


class GoogleSessionBlocked(SessionBlocked):
    # Ошибка применяется когда гугл забраковал браузер и не позволяет работать
    # или произошла ошибка при получении страницы - "ErrorPage"
    log_level = "warning"


class GoogleSessionCaptchaDecodeError(SessionCaptchaDecodeError):
    log_level = "warning"


class GoogleSourceOperationFailure(SourceOperationFailure):
    log_level = "warning"
