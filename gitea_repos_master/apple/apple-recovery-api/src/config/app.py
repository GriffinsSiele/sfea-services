"""
Модуль содержит настройки приложения, который меняются редко и только при внесении изменений в код приложения.
"""


class ConfigApp:
    BASE_URL = "https://iforgot.apple.com"
    FORM_URL = BASE_URL + "/password/verify/appleid"
    CAPTCHA_IMAGE_URL = BASE_URL + "/captcha"

    CAPTCHA_SOURCE = "apple-recovery"
    CAPTCHA_TIMEOUT = 5  # seconds

    TASK_TIMEOUT = 60

    CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME = 3 * 60  # seconds
