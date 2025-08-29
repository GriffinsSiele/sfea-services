from isphere_exceptions.session import SessionCaptchaDecodeError


class SessionCaptchaDecodeWarning(SessionCaptchaDecodeError):
    log_level = "warning"
