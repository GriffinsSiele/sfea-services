class ConfigApp:
    # Наименование источника, для которого решается капча:
    CAPTCHA_SOURCE = "xiaomi-recovery"
    # Время в течение которого ожидается получить ответ с решением капчи:
    CAPTCHA_TIMEOUT = 40  # секунд
    # Максимальное время ожидания загрузки главного экрана и изображения с капчей:
    SCREEN_WAITING = 3  # секунды
    # Максимальное время ожидания загрузки результата поиска:
    SEARCH_RESULT_WAITING = 4  # секунды
    # Максимальное время ожидания загрузки результата поиска:
    MAX_GET_ELEMENT_ATTEMPTS = 3
    # Множитель количества перебора элементов при поиске нужного (см. класс XiaomiBrowser):
    SEARCH_ATTEMPT_MULTIPLIER = 10
    # URL стартовой страницы:
    MAIN_PAGE_URL = "https://account.xiaomi.com/helpcenter/service/forgetPassword"
