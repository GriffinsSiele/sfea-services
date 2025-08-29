class ConfigApp:
    # URL стартовой страницы:
    MAIN_PAGE_URL_HUAWEI = (
        "https://id5.cloud.huawei.com/AMW/portal/resetPwd/forgetbyid.html"
    )
    MAIN_PAGE_URL_HONOR = (
        "https://hnid-drru.cloud.hihonor.com/AMW/portal/resetPwd/forgetbyid.html"
    )

    # Ожидание загрузки экранов
    # Множитель количества перебора элементов при поиске нужного (см. класс ExtendedBrowser):
    MULTIPLIER = 10
    # Максимальное время ожидания загрузки главного экрана:
    WAITING_MAIN = 5  # секунд
    # Максимальное время ожидания загрузки капчи:
    WAITING_CAPTCHA = 15  # секунд
    # Максимальное время ожидания, пока закроется окно с капчей
    WAITING_CAPTCHA_CHECK = 3  # секунды
    # Максимальное время ожидания загрузки дополнительного экрана:
    WAITING_EXTRA = 3  # секунды
    # Максимальное время ожидания загрузки экрана с результатом поиска:
    WAITING_RESULT = 5  # секунд
    # Максимальное время загрузки экрана, по истечении которого сработает Timeout селениума.
    MAX_PAGE_LOAD_TIMEOUT = 20
