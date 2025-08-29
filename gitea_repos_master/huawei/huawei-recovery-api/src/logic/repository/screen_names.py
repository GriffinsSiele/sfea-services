from enum import StrEnum


class ScreenNames(StrEnum):
    """Содержит имена экранов."""

    MAIN = "main"
    CAPTCHA = "captcha"
    EXTRA = "extra"
    BLOCKED = "blocked"
    RESULT_FOUND = "result_found"
    RESULT_NOT_FOUND = "result_not_found"

    def __repr__(self):
        return str(repr(self._value_))
