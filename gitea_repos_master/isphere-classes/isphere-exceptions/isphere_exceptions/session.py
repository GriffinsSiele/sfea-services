from typing import Optional

from isphere_exceptions import (
    BlockedException,
    CommonException,
    ErrorNoReturnToQueue,
    ErrorReturnToQueue,
    ISphereException,
    LockedException,
)


class SessionErrorInterface(ISphereException):
    """Интерфейс ошибки для работы с сессиями

    Ошибка с кодом 526, префикс SSS
    """

    EXCEPTION_PREFIX = "SSS"
    TEMPLATE_CONTEXT: Optional[str] = "Сессия"

    def __init__(self, *args, code=526, **kwargs):
        super().__init__(*args, code=code, **kwargs)


class SessionError(SessionErrorInterface, CommonException):
    DEFAULT_MESSAGE = "Ошибка использований сессии"


class SessionOutdated(SessionErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Истек срок жизни сессии"

    def __init__(self, *args, internal_code=501, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class SessionInvalidCredentials(SessionErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Неверные авторизационные данные"

    def __init__(self, *args, internal_code=502, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class SessionEmpty(SessionErrorInterface, ErrorNoReturnToQueue):
    DEFAULT_MESSAGE = "Недостаток сессий"

    livenessprobe = True
    log_level = "error"

    def __init__(self, *args, internal_code=503, code=512, **kwargs):
        super().__init__(*args, internal_code=internal_code, code=code, **kwargs)


class SessionCaptchaDecodeError(SessionErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Возникла ошибка расшифровки капчи"

    def __init__(self, *args, internal_code=504, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class SessionCaptchaDetected(SessionErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Обнаружена капча во время использования сессии"

    def __init__(self, *args, internal_code=505, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)


class SessionLocked(SessionErrorInterface, LockedException):
    pass


class SessionBlocked(SessionErrorInterface, BlockedException):
    pass


class SessionLimitError(SessionErrorInterface, ErrorReturnToQueue):
    DEFAULT_MESSAGE = "Превышен лимит использования сессии"

    def __init__(self, *args, internal_code=508, **kwargs):
        super().__init__(*args, internal_code=internal_code, **kwargs)
