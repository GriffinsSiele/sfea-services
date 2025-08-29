"""
Модуль содержит настройки приложения, который меняются редко и только при внесении изменений в код приложения
"""

__all__ = ("ConfigApp",)


class ConfigAuth:
    """Содержит настройки для приложения auth"""

    START_URL = "https://v3.account.samsung.com/dashboard/intro"
    SESSION_URL = (
        "https://account.samsung.com/accounts/v1/MYACCOUNT3/signInIdentificationProc"
    )
    MULTIPLE_WAIT = 3
    SESSION_MAX_USE = 9
    TIME_SESSION_BECOMES_OLD = {
        "minutes": 10
    }  # время, по истечении которого сессия считается старой
    TIME_SESSION_PROLONG = {
        "minutes": 8
    }  # время, по истечении которого сессия считается старой


class ConfigPerson:
    """Содержит настройки для приложения person"""

    START_URL = "https://account.samsung.com/accounts/v1/MBR/findIdWithUserInfo"
    SESSION_URL = "https://account.samsung.com/accounts/v1/MBR/findIdProc"


class ConfigName(ConfigPerson):
    """Содержит настройки для приложения name"""

    START_URL = "https://account.samsung.com/accounts/v1/MBR/findId"
    SESSION_URL = "https://account.samsung.com/accounts/v1/MBR/findIdWithRecoveryProc"


class ConfigNamePerson:
    """Содержит настройки для приложения name и person"""

    MULTIPLE_WAIT = 3
    SESSION_MAX_USE = 4
    TIME_SESSION_BECOMES_OLD = {
        "minutes": 10
    }  # время, по истечении которого сессия считается старой
    TIME_SESSION_PROLONG = {
        "minutes": 8
    }  # время, по истечении которого сессия считается старой


class ConfigApp:
    """Содержит настройки приложения"""

    # URL после отправки запроса на который reCAPTCHA считается загруженной и можно отправлять запрос поиска
    RECAPTCHA_URL = "https://www.recaptcha.net/recaptcha/enterprise/reload"
    MAX_PAGE_LOAD_TIMEOUT = 20  # максимальное время загрузки страницы в браузере
    # максимальный процент ошибок допустимый для обработчика, после которого начинают выдаваться ошибки
    MAX_ERRORS_PERCENT = 30

    auth = ConfigAuth
    person = ConfigPerson
    name = ConfigName
    name_person = ConfigNamePerson
