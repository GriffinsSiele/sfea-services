"""
Пакет для работы с сервисом капч.
"""

from .captcha_exceptions import CaptchaConfiguredError
from .captcha_service import CaptchaService

__all__ = ("CaptchaService", "CaptchaConfiguredError")
