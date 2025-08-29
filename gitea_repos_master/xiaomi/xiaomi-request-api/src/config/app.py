"""
Модуль содержит настройки приложения, который меняются редко и только при внесении изменений в код приложения.
"""


class ConfigApp:
    BASE_URL = "https://verify.sec.xiaomi.com/captcha/v2/data"
    GET_CAPTCHA_IMG_URL = "https://verify.sec.xiaomi.com/captcha/v2/image/register"
    POST_CAPTCHA_IMG_URL = "https://verify.sec.xiaomi.com/captcha/v2/image/verify"
    RESULT_URL = "https://account.xiaomi.com/pass/forgetPassword"

    K_PARAM = "8027422fb0eb42fbac1b521ec4a7961f"

    PUBLIC_KEY = ""

    CAPTCHA_SOURCE = "xiaomi-recovery"
    CAPTCHA_TIMEOUT = 40  # seconds

    TASK_TIMEOUT = 60

    CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME = 10 * 60  # seconds
