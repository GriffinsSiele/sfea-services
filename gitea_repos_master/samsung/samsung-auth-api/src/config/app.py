"""
Модуль содержит настройки приложения, который меняются редко и только при внесении изменений в код приложения.
"""


class ConfigApp:
    """Содержит настройки приложения"""

    BASE_URL_AUTH = "https://v3.account.samsung.com/api/v1/signin/auths/accounts"
    BASE_URL_PERSON = "https://account.samsung.com/accounts/v1/MBR/findIdProc"
    BASE_URL_NAME = "https://account.samsung.com/accounts/v1/MBR/findIdWithRecoveryProc"
    TASK_TIMEOUT = 60
