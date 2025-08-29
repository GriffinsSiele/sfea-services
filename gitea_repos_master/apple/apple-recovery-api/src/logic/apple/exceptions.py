from isphere_exceptions.session import SessionCaptchaDecodeError
from isphere_exceptions.source import SourceError


class SessionCaptchaDecodeWarning(SessionCaptchaDecodeError):
    log_level = "warning"


class SourceWarning(SourceError):
    log_level = "warning"
