"""
Инициализация экранов:
main_definitions: основные теги, которые позволяют отличить данный экран от других.
secondary_definitions: дополнительные теги, которые позволяют отличить данный экран от других.
follow: ссылки для перехода на следующие экраны и список экранов, на которые можно перейти по данной ссылке.
dispatcher: обработчик, собирает данные с экрана.
"""

from src.logic.google.configured_screen import Screen
from src.logic.google.screen_dispatchers import *
from src.logic.google.screen_repository import ScreensRepository

MAIN_PAGE_TITLE = "MainPage"
MAIN_PAGE_INPUT = "identifierId"
MAIN_SIMILAR_SCREENS = [
    "NotFoundError_1",
    "NotFoundError_2",
    "SourceIncorrectData",
    "CaptchaPage_1",
]
CAPTCHA_SCREENS_FOR_SOLVING = ("CaptchaPage_1",)
CAPTCHA_NOT_SOLVED_SCREENS = ("CaptchaNotSolvedPage",)
EXTERNAL_SCREEN_NAME = "ExternalScreen"

screens_repository = ScreensRepository()

screens_repository.add_page(
    MAIN_PAGE_TITLE,
    Screen.create_screen_without_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (
                By.XPATH,
                '//*[@id="identifierId"][@aria-label="Телефон или адрес эл. почты"]',
            ),
            (By.XPATH, '//*/button[contains(text(), "Забыли адрес электронной почты?")]'),
            (
                By.XPATH,
                '//*[@id="identifierNext"]/div/button/span[contains(text(), "Далее")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//*[@id="identifierNext"]/div/button/span[contains(text(), "Далее")]',
                ),
                "screens": [
                    "WellcomePage_1",
                    "WellcomePage_2",
                    "NotFoundError_1",
                    "NotFoundError_2",
                    "SourceIncorrectData",
                    "CaptchaPage_1",
                    "CaptchaPage_2",
                    "InsecureBrowser",
                    "ErrorPage",
                ],
            }
        ],
    ),
)

screens_repository.add_page(
    "WellcomePage_1",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["AnotherWayPage_1", "AnotherWayPage_2"],
            }
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "WellcomePage_2",
    Screen.create_screen_with_payload(
        main_definitions=[
            (By.XPATH, '//button/span[contains(text(), "Забыли пароль?")]')
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//input[@type="password"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Забыли пароль?")]'),
                "screens": [
                    "RecoveryPage_1",
                    "RecoveryPage_13",
                    "RecoveryPage_14",
                    "RecoveryPage_15",
                    "RecoveryPage_18",
                    "RecoveryPage_25",
                    "RecoveryPage_31",
                    "TooManyFailedAttempts",
                ],
            },
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "WellcomePage_3",  # содержит модальное окно
    Screen.create_end_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Подтверждение личности…")]',
            ),
            (
                By.XPATH,
                '//form/span/section[1]/div/div/div[contains(text(), "Войдите в аккаунт, используя ")]',
            ),
            (
                By.XPATH,
                '//button/span[contains(text(), "Другой способ")]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "AnotherWayPage_1",
    Screen.create_screen_without_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (By.XPATH, '//ul/li[2]/div/div[2][contains(text(), "Справка")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//ul/li[2]/div/div[2][contains(text(), "Справка")]'),
                "screens": ["RecoveryPage_1", "EndPage5"],
            }
        ],
    ),
)

screens_repository.add_page(
    "AnotherWayPage_2",
    Screen.create_screen_with_payload(
        main_definitions=[
            (
                By.XPATH,
                '//ul/li[2]/div/div[2][contains(text(), "Использовать ключ доступа")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
        ],
        follow=[
            # (
            #     By.XPATH,
            #     '//ul/li[2]/div/div[2][contains(text(), "Использовать ключ доступа")]',
            # ),
            {
                "link": (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
                "screens": [
                    "RecoveryPage_5",
                    "WellcomePage_3",
                ],  # может не так, мешают подключенные устройства
            }
        ],
        dispatcher=AnotherWayPage2andRecoveryPage27Dispatcher(),
    ),
)

screens_repository.add_page(
    "AnotherWayPage_3",
    Screen.create_screen_without_payload(
        main_definitions=[
            (
                By.XPATH,
                '//ul/li[1]/div/div[2][contains(text(), "Нажать ")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (By.XPATH, '//ul/li[2]/div/div[2][contains(text(), "Введите пароль")]'),
            (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
        ],
        follow=[
            # (
            #     By.XPATH,
            #     '//ul/li[1]/div/div[2][contains(text(), "Нажать ")]',
            # ),
            {
                "link": (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
                "screens": ["WellcomePage_1", "WellcomePage_2"],
            }
        ],
    ),
)

screens_repository.add_page(
    # Использовать ключ доступа - возвращает на предыдущую страницу.
    # Введите пароль - переводит на страницу ввода пароля, с которой возвращает на эту страницу (зацикливание).
    # Справка - позволяет пройти дальше.
    "AnotherWayPage_4",
    Screen.create_screen_without_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Добро пожаловать!")]'),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (
                By.XPATH,
                '//ul/li[1]/div/div[2][contains(text(), "Использовать ключ доступа")]',
            ),
            (By.XPATH, '//ul/li[2]/div/div[2][contains(text(), "Введите пароль")]'),
            (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//ul/li[3]/div/div[2][contains(text(), "Справка")]'),
                "screens": ["WellcomePage_1", "WellcomePage_2"],
            }
        ],
    ),
)

screens_repository.add_page(
    "RecoveryPage_1",
    Screen.create_screen_without_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="password"]/div[1]/div/div[1]/div[contains(text(), '
                '"Введите последний пароль")]',
            ),  # input
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                "//form/span/section[3]/div/div/div[1][contains(text(), "
                '"Введите последний пароль этого аккаунта, который помните")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": [
                    "RecoveryPage_2",
                    "RecoveryPage_4",
                    "RecoveryPage_5",
                    "RecoveryPage_15",
                    "RecoveryPage_17",
                    "RecoveryPage_18",
                    "RecoveryPage_22",
                    "RecoveryPage_23",
                    "RecoveryPage_30",
                ],
            }
        ],
    ),
)

screens_repository.add_page(
    "RecoveryPage_2",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте оповещения на вашем устройстве.")]',
            ),
            (By.XPATH, '//section/div/div/div[contains(text(), "На устройстве")]'),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3", "RecoveryPage_4", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage2and17and46Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_3",  # соответствует RecoveryPage_16
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                "//section[1]/div/div/div/div/ul/li[1]/div/strong",
            ),  # всплывающее окно с выбором!
            (
                By.XPATH,
                '//ul/li[1]/div[contains(text(), "Войдите в аккаунт на устройстве ")]',
            ),
            (By.XPATH, '//ul/li[2]/div[contains(text(), "Откройте приложение ")]'),
            (By.XPATH, '//ul/li[3]/div[contains(text(), "Нажмите ")]'),
            (
                By.XPATH,
                '//ul/li[4]/div[contains(text(), "Выберите аккаунт, если он не указан.")]',
            ),
            (By.XPATH, '//ul/li[5]/div[contains(text(), "Нажмите ")]'),
            (
                By.XPATH,
                '//ul/li[6]/div[contains(text(), " (при необходимости прокрутите вправо).")]',
            ),
            (
                By.XPATH,
                '//ul/li[6]/div[contains(text(), " (при необходимости прокрутите вправо).")]',
            ),
            (By.XPATH, '//ul/li[7]/div[contains(text(), "Вход в аккаунт Google")]'),
            (
                By.XPATH,
                '//ul/li[8]/div[contains(text(), "Чтобы получить код, выберите аккаунт")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage3and16and28and33and34Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_4",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//input[@aria-label="Введите резервный адрес электронной почты"]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_3", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage4Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_5",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//*[@id="phoneNumberId"]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3", "RecoveryPage_32", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage5and14Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_6",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//form/span/section[1]/div/div/div[contains(text(), "Создайте код в приложении ")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage6and7and23and30Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_7",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Получите код подтверждения")]',
            ),
            (
                By.XPATH,
                "//form/span/section[3]/div/div/div[1][contains(text(), "
                '"Код подтверждения будет отправлен на номер ")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_8", "RecoveryPage_26"],
            }
        ],
        dispatcher=RecoveryPage6and7and23and30Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_8",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            # Получить письмо с кодом подтверждения на cry•••••••@gmail.com
            (
                By.XPATH,
                '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            # Получить письмо с кодом подтверждения на f••••@yan•••.••
            (
                By.XPATH,
                '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            # Получить письмо с кодом подтверждения на f••••@yan•••.••
            (
                By.XPATH,
                '//ul/li[3]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[4]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно четыре ссылки, по первым трем нет смысла ходить,
            # так как попадаем на экран RecoveryPage_9, на котором можно получить
            # часть e-mail адреса, который уже получили на этом экране,
            # с экрана RecoveryPage_9 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    '//ul/li[4]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
                ),
                "screens": ["EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[3]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
        ],
        dispatcher=RecoveryPage8Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_9",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//*[@id="idvPinId"]'),
            (
                By.XPATH,
                "//form/span/section[2]/div/div/div[1][contains(text(), "
                '"Письмо с кодом подтверждения отправлено на адрес")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_8"],
            }
        ],
        dispatcher=RecoveryPage9Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_11",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте оповещения на вашем устройстве.")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Потяните панель уведомлений вниз")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage11and13Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_12",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//h2/span[contains(text(), "Получите код подтверждения")]'),
            (
                By.XPATH,
                "//form/span/section[3]/div/div/div[1][contains(text(), "
                '"Чтобы получить код подтверждения, введите номер телефона, '
                'указанный в настройках безопасности аккаунта")]',
            ),
            (By.XPATH, '//*[@id="phoneNumberId"]'),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_20"],
            }
        ],
        dispatcher=RecoveryPage12Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_13",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте телефон")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Потяните панель уведомлений вниз ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "Другой способ")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "Другой способ")]',
                ),
                "screens": ["RecoveryPage_6", "RecoveryPage_19", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage11and13Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_14",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//*[@id="phoneNumberId"]'),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_13", "RecoveryPage_19", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage5and14Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_15",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте устройство ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": [
                    "RecoveryPage_3",
                    "RecoveryPage_4",
                    "RecoveryPage_3",  # <<< "RecoveryPage_16"
                    "RecoveryPage_33",
                ],
            }
        ],
        dispatcher=RecoveryPage15Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_17",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте телефон")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "На устройстве ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3", "RecoveryPage_4"],
            }
        ],
        dispatcher=RecoveryPage2and17and46Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_18",
    Screen.create_screen_with_payload(
        main_definitions=[
            (
                By.XPATH,
                '//h2/span[contains(text(), "Откройте приложение")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_4", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage18Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_19",
    Screen.create_screen_with_payload(
        main_definitions=[
            (
                By.XPATH,
                '//form/span/section[1]/div/div/div[contains(text(), "Ввести 8-значный резервный код")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите резервный код"]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "Другой способ")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "Другой способ")]',
                ),
                "screens": ["RecoveryPage_4", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage19Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_20",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Используйте номер телефона, добавленный в аккаунт")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Мы отправим код подтверждения '
                'на номер телефона, указанный в аккаунте.")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (By.XPATH, "//section/div/div/div/ul/li[1]/div/div[2]/div[1]"),
            (
                By.XPATH,
                "//section/div/div/div/ul/li[2]/div/div[2][contains(text(), "
                '"Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно две ссылки, по первой с телефоном нет смысла ходить,
            # так как попадаем на экран RecoveryPage_12, на котором можно получить
            # часть телефонного номера, которую уже получили на этом экране,
            # с экрана RecoveryPage_12 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[2]/div/div[2][contains(text(), "
                    '"Выбрать другой способ входа")]',
                ),
                "screens": ["RecoveryPage_26", "EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_12"],
            },
        ],
        dispatcher=RecoveryPage20Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_21",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Используйте номер телефона, добавленный в аккаунт")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Мы отправим код подтверждения '
                'на номер телефона, указанный в аккаунте.")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (
                By.XPATH,
                "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span",
            ),  # •••••••••15
            (
                By.XPATH,
                "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span",
            ),  # 8 (911) 564-23-90
            (
                By.XPATH,
                '//section/div/div/div/ul/li[3]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно три ссылки, по ним нет смысла ходить, так как попадаем на экран RecoveryPage_7,
            # на котором можно получить те же данные, что и на этом экране,
            # с экрана RecoveryPage_7 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    '//section/div/div/div/ul/li[3]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
                ),
                "screens": ["EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_7"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_7"],
            },
        ],
        dispatcher=RecoveryPage21Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_22",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте телефон")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено на устройства ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_23", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage22and50Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_23",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//form/span/section[1]/div/div/div[contains(text(), "Укажите имя и фамилию для аккаунта Google")]',
            ),
            (By.XPATH, '//*[@id="firstName" and @aria-label="Имя"]'),
            # убрано для совместимости с "Фамилия (необязательно)"
            # (By.XPATH, '//*[@id="lastName" and @aria-label="Фамилия"]'),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_4"],
            }
        ],
        dispatcher=RecoveryPage6and7and23and30Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_24",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте оповещения на вашем устройстве.")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено на устройства")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage24Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_25",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте телефон")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено на ваш телефон")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "Другой способ")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "Другой способ")]',
                ),
                "screens": ["RecoveryPage_6"],
            }
        ],
        dispatcher=RecoveryPage25and31and39Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_26",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (
                By.XPATH,
                '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[3]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно три ссылки, по первым двум нет смысла ходить,
            # так как попадаем на экран RecoveryPage_9, на котором можно получить
            # часть e-mail адреса, который уже получили на этом экране,
            # с экрана RecoveryPage_9 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    '//ul/li[3]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
                ),
                "screens": ["EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
        ],
        dispatcher=RecoveryPage26Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_27",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                "//h1/span[contains(text(), "
                '"Используйте отпечаток пальца, распознавание лица '
                'или выбранный способ разблокировки экрана")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div[contains(text(), "
                '"Вы можете подтверждать свою личность с помощью ключа доступа. '
                "Прежде чем использовать выбранный способ разблокировки экрана, "
                'вам может потребоваться отсканировать QR-код.")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Продолжить")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=AnotherWayPage2andRecoveryPage27Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_28",  # RecoveryPage_35
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                "//section[1]/div/div/div/div/ul/li[1]/div/strong",
            ),  # всплывающее окно с выбором!
            (
                By.XPATH,
                '//ul/li[1]/div[contains(text(), "Войдите в аккаунт на устройстве ")]',
            ),
            (By.XPATH, '//ul/li[2]/div[contains(text(), "Откройте приложение ")]'),
            (
                By.XPATH,
                '//ul/li[3]/div[contains(text(), "Выберите ")]',
            ),
            (
                By.XPATH,
                '//ul/li[5]/div[contains(text(), " (при необходимости прокрутите вправо).")]',
            ),
            (By.XPATH, '//ul/li[6]/div[contains(text(), "Вход в аккаунт Google")]'),
            (
                By.XPATH,
                '//ul/li[7]/div[contains(text(), "Чтобы получить код, выберите аккаунт")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage3and16and28and33and34Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_29",
    Screen.create_screen_without_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Использовать номер телефона, добавленный в аккаунт?")]',
            ),
            (
                By.XPATH,
                '//form/span/section[3]/div/div/div[contains(text(), "Чтобы помочь вам восстановить аккаунт, '
                'мы можем отправить вам код на другой номер, который вы добавили в аккаунт. ")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Отправить")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
    ),
)

screens_repository.add_page(
    "RecoveryPage_30",
    Screen.create_screen_with_payload(
        main_definitions=[
            (
                By.XPATH,
                "//h2/span[contains(text(), 'Отсканируйте этот QR-код с помощью приложения ')]",
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_23"],
            }
        ],
        dispatcher=RecoveryPage6and7and23and30Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_31",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте оповещения на вашем устройстве.")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено на ваш телефон")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "Другой способ")]',
                ),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage25and31and39Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_32",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Использовать номер телефона, добавленный в аккаунт?")]',
            ),
            (
                By.XPATH,
                "//form/span/section[3]/div/div/div[contains(text(), "
                '"Чтобы помочь вам восстановить аккаунт, мы можем отправить вам код '
                'на другой номер, который вы добавили в аккаунт. ")]',
            ),
            (
                By.XPATH,
                "//form/span/section[3]/div/div/div[1]/div[contains(text(), "
                '"Чтобы получить код для входа, подтвердите номер телефона: ")]',
            ),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Отправить")]'),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3"],
            }
        ],
        dispatcher=RecoveryPage32Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_33",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, "//section[1]/div/div/div/div/ul/li[1]/div/strong"),
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//ul/li[1]/div[contains(text(), "Войдите в аккаунт на устройстве ")]',
            ),
            (By.XPATH, '//ul/li[2]/div[contains(text(), "Откройте приложение ")]'),
            (By.XPATH, '//ul/li[3]/div[contains(text(), "Нажмите ")]'),
            (
                By.XPATH,
                '//ul/li[4]/div[contains(text(), "Выберите ")]',
            ),
            (
                By.XPATH,
                '//ul/li[5]/div[contains(text(), "Чтобы получить код, выберите аккаунт")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage3and16and28and33and34Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_34",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, "//section[1]/div/div/div/div/ul/li[1]/div/strong"),
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//ul/li[1]/div[contains(text(), "Войдите в аккаунт на устройстве ")]',
            ),
            (By.XPATH, '//ul/li[2]/div[contains(text(), "Откройте приложение ")]'),
            (By.XPATH, '//ul/li[3]/div[contains(text(), "Нажмите ")]'),
            (By.XPATH, '//ul/li[4]/div[contains(text(), "Нажмите ")]'),
            (
                By.XPATH,
                '//ul/li[5]/div[contains(text(), "Выберите ")]',
            ),
            (
                By.XPATH,
                '//ul/li[6]/div[contains(text(), "Чтобы получить код, выберите аккаунт")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage3and16and28and33and34Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_36",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Используйте номер телефона, добавленный в аккаунт")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Мы отправим код подтверждения '
                'на номер телефона, указанный в аккаунте.")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (
                By.XPATH,
                "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span",
            ),  # 0554 900 85 57
            (
                By.XPATH,
                "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span",
            ),  # •••••••••46
            (
                By.XPATH,
                "//section/div/div/div/ul/li[3]/div/div[2]/div[1]/span/span",
            ),  # •••••••••01
            (
                By.XPATH,
                '//section/div/div/div/ul/li[4]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно четыре ссылки, по ним нет смысла ходить, так как попадаем на экран RecoveryPage_7,
            # на котором можно получить те же данные, что и на этом экране,
            # с экрана RecoveryPage_7 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    '//section/div/div/div/ul/li[4]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
                ),
                "screens": ["EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_7"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_7"],
            },
            {
                "link": (
                    By.XPATH,
                    "//section/div/div/div/ul/li[3]/div/div[2]/div[1]/span/span",
                ),
                "screens": ["RecoveryPage_7"],
            },
        ],
        dispatcher=RecoveryPage36Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_37",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Восстановление аккаунта")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Для защиты аккаунта мы должны убедиться, что это действительно вы пытаетесь войти в систему")]',
            ),
            (By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'),
            (
                By.XPATH,
                '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[3]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[4]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
            ),
            (
                By.XPATH,
                '//ul/li[5]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
            ),
        ],
        follow=[
            # Доступно пять ссылок, по первым четырем нет смысла ходить,
            # так как попадаем на экран RecoveryPage_9, на котором можно получить
            # часть e-mail адреса, который уже получили на этом экране,
            # с экрана RecoveryPage_9 опять попадаем на этот экран
            {
                "link": (
                    By.XPATH,
                    '//ul/li[5]/div/div[2][contains(text(), "Выбрать другой способ входа")]',
                ),
                "screens": ["EndPage"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[1]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[2]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[3]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
            {
                "link": (
                    By.XPATH,
                    '//ul/li[4]/div/div[2][contains(text(), "Получить письмо с кодом подтверждения на")]',
                ),
                "screens": ["RecoveryPage_9"],
            },
        ],
        dispatcher=RecoveryPage37Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_38",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//ul/li[1]/div[contains(text(), "Войдите в аккаунт на устройстве ")]',
            ),
            (By.XPATH, "//section[1]/div/div/div/div/ul/li[1]/div/strong"),
            (By.XPATH, '//ul/li[2]/div[contains(text(), "Откройте приложение ")]'),
            (By.XPATH, '//ul/li[3]/div[contains(text(), "Откройте меню действий")]'),
            (
                By.XPATH,
                '//ul/li[4]/div[contains(text(), "Чтобы получить код, выберите аккаунт")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage3and16and28and33and34Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_39",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//h2/span[contains(text(), "Проверьте оповещения на вашем устройстве.")]',
            ),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено на ваш телефон Чтобы подтвердить свою личность, нажмите в уведомлении ")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=RecoveryPage25and31and39Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_40",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Подтвердите личность с помощью ключа доступа")]',
            ),
            (
                By.XPATH,
                "//form/span/section[1]/div/div/div[contains(text(), "
                '"Используйте отпечаток пальца, функцию распознавания лица или блокировки экрана.")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Продолжить")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["AnotherWayPage_4"],
            }
        ],
        dispatcher=AnotherWayPage2andRecoveryPage27Dispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_41",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Подтвердите личность с помощью телефона")]',
            ),
            (
                By.XPATH,
                "//form/span/section[1]/div/div/div[contains(text(), "
                '"Google проверит наличие Bluetooth-сигнала, чтобы определить, находитесь ли вы рядом с телефоном.")]',
            ),
            (
                By.XPATH,
                "//form/span/section[1]/div/div/div[2][contains(text(), "
                '"Это поможет убедиться, что запрос на вход не поступает '
                'от постороннего человека из другого местоположения.")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Продолжить")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage"],
            }
        ],
        dispatcher=UserFoundPhoneDispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_42",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Выберите аккаунт")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Какой из них использовать?")]',
            ),
            (By.XPATH, "//form/span/section/div/div/div/ul/li[1]/div"),
            (By.XPATH, "//form/span/section/div/div/div/ul/li[2]/div"),
        ],
        follow=[
            {
                "link": (By.XPATH, "//form/span/section/div/div/div/ul/li[1]/div"),
                "screens": [
                    "WellcomePage_1",
                    "WellcomePage_2",
                ],
            },
            {
                "link": (By.XPATH, "//form/span/section/div/div/div/ul/li[2]/div"),
                "screens": [
                    "WellcomePage_1",
                    "WellcomePage_2",
                ],
            },
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_43",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Отправьте код на резервный номер телефона")]',
            ),
            (
                By.XPATH,
                '//*[@id="yDmH0d"]/c-wiz/div/div[2]/div/div/div/form/span/section[3]/div/div/div[contains(text(), '
                '"Код подтверждения будет отправлен на номер")]',
            ),
            (
                By.XPATH,
                '//button/span[contains(text(), "Отправить код")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": [
                    "WellcomePage_1",
                    "WellcomePage_2",
                ],
            },
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "RecoveryPage_44",  # new RecoveryPage_15
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Проверьте устройство")]'),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Потяните панель уведомлений вниз")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": [
                    "RecoveryPage_3",
                    "RecoveryPage_4",
                    "RecoveryPage_3",  # <<< "RecoveryPage_16"
                    "RecoveryPage_33",
                ],
            }
        ],
        dispatcher=RecoveryPage44Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_45",  # new RecoveryPage_18
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Откройте приложение")]',
            ),
            (By.XPATH, "//figure/samp"),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_4", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage45Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_46",  # new RecoveryPage_17
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Проверьте телефон")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "На устройстве ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3", "RecoveryPage_4"],
            }
        ],
        dispatcher=RecoveryPage2and17and46Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_47",  # new RecoveryPage_5
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Чтобы получить код, подтвердите резервный номер телефона")]',
            ),
            (By.XPATH, '//*[@id="phoneNumberId"]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_3", "RecoveryPage_32", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage47Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_48",  # new RecoveryPage_4
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Чтобы получить код, подтвердите резервный адрес электронной почты")]',
            ),
            (
                By.XPATH,
                '//input[@aria-label="Введите резервный адрес электронной почты"]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_3", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage48and49Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_49",  # new RecoveryPage_4
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Введите полученный код")]',
            ),
            (By.XPATH, '//input[@aria-label="Введите код"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["RecoveryPage_3", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage48and49Dispatcher(),
    ),
)


screens_repository.add_page(
    "RecoveryPage_50",  # new RecoveryPage_22
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Проверьте телефон")]',
            ),
            (By.XPATH, "//figure/samp"),
            (
                By.XPATH,
                '//section/div/div/div[contains(text(), "Уведомление отправлено ")]',
            ),
            # страница загружена, но это поле может быть какое то время недоступно
            # (By.XPATH, '//button/span[contains(text(), "Отправить ещё раз")]'),
            (
                By.XPATH,
                '//button/span[contains(text(), "У меня нет доступа к телефону")]',
            ),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//button/span[contains(text(), "У меня нет доступа к телефону")]',
                ),
                "screens": ["RecoveryPage_23", "EndPage"],
            }
        ],
        dispatcher=RecoveryPage22and50Dispatcher(),
    ),
)


screens_repository.add_page(
    "CaptchaPage_1",  # соответствует новому дизайну
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="captchaimg" and @src]'),
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": [
                    "SourceIncorrectDataCaptchaPage",
                    "NotFoundError1CaptchaPage",
                    "NotFoundError2CaptchaPage",
                    "CaptchaNotSolvedPage",
                    "WellcomePage_1",
                    "WellcomePage_2",
                ],
            }
        ],
        dispatcher=CaptchaDispatcher(),
    ),
)

screens_repository.add_page(
    "CaptchaNotSolvedPage",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//form/span/section/div/div/div[4]/div[2]/div/div[2]/div[2]/div[contains(text(), "Снова введите символы, которые показаны на изображении выше.")]',
            ),
            (By.XPATH, '//*[@id="captchaimg"]'),
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": [],
            }
        ],
        dispatcher=CaptchaNotSolvedDispatcher(),
    ),
)

screens_repository.add_page(
    "SourceIncorrectDataCaptchaPage",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="captchaimg"]'),
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
            (
                By.XPATH,
                '//form/span/section/div/div/div[1]/div/div[2]/div[2]/div[contains(text(), "Введите адрес электронной почты или номер телефона.")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": [],
            }
        ],
        dispatcher=SourceIncorrectDataDispatcher(),
    ),
)

screens_repository.add_page(
    "NotFoundError1CaptchaPage",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="captchaimg"]'),
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
            (
                By.XPATH,
                '//form/span/section/div/div/div[1]/div/div[2]/div[2]/div[contains(text(), "Аккаунт Google не найден. Попробуйте ввести адрес электронной почты.")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": [],
            }
        ],
        dispatcher=NotFoundErrorDispatcher(),
    ),
)

screens_repository.add_page(
    "NotFoundError2CaptchaPage",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="captchaimg"]'),
            (By.XPATH, '//h1/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
            (
                By.XPATH,
                '//form/span/section/div/div/div[1]/div/div[2]/div[2]/div[contains(text(), "Не удалось найти аккаунт Google.")]',
            ),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": [],
            }
        ],
        dispatcher=NotFoundErrorDispatcher(),
    ),
)

screens_repository.add_page(
    "CaptchaPage_2",  # соответствует новому дизайну
    Screen.create_screen_with_payload(
        main_definitions=[
            (
                By.XPATH,
                '//form/span/section/div/div/div[1][contains(text(), "Докажите, что вы не робот")]',
            ),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingText"]/span[contains(text(), "Подтвердите свою личность")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, '
                'что это действительно вы пытаетесь войти в систему ")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Другой способ")]'),
                "screens": ["EndPage6"],
            },
        ],
        dispatcher=ReCaptchaDispatcher(),
    ),
)

screens_repository.add_page(
    "NotFoundError_1",  # содержит поля CaptchaPage_1
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div[1]/div/div[2]/div[2]/div[contains(text(),"
                ' "Аккаунт Google не найден. Попробуйте ввести адрес электронной почты.")]',
            ),
            (By.XPATH, '//*[@id="headingText"]/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//*/button[contains(text(), "Забыли адрес электронной почты?")]'),
        ],
        dispatcher=NotFoundErrorDispatcher(),
    ),
)

screens_repository.add_page(
    "NotFoundError_2",
    Screen.create_error_screen(
        main_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div[1]/div/div[2]/div[2]/div[contains(text(), "
                '"Не удалось найти аккаунт Google.")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//*[@id="headingText"]/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//*/button[contains(text(), "Забыли адрес электронной почты?")]'),
        ],
        dispatcher=NotFoundErrorDispatcher(),
    ),
)

screens_repository.add_page(
    "SourceIncorrectData",
    Screen.create_error_screen(
        main_definitions=[
            (
                By.XPATH,
                '//*[@id="yDmH0d"]/c-wiz/div/div[2]/div/div[1]/div/form/span/section/div/div/'
                "div[1]/div/div[2]/div[2]/div[contains(text(), "
                '"Введите адрес электронной почты или номер телефона.")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//*[@id="headingText"]/span[contains(text(), "Вход")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Используйте аккаунт Google")]',
            ),
            (By.XPATH, '//*/button[contains(text(), "Забыли адрес электронной почты?")]'),
        ],
        dispatcher=SourceIncorrectDataDispatcher(),
    ),
)

screens_repository.add_page(
    "SourceIncorrectData_2",
    Screen.create_error_screen(
        main_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div/div/div[2]/div[2]/div[contains(text(), "
                '"Недействительный адрес электронной почты или номер телефона")]',
            ),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingText"]/span[contains(text(), "Ваш адрес электронной почты")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Укажите номер телефона или резервный адрес электронной почты")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        dispatcher=SourceIncorrectDataDispatcher(),
    ),
)

screens_repository.add_page(
    "ErrorPage",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingText"]/span[contains(text(), "Произошла ошибка")]',
            ),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Произошла ошибка. Повторите попытку.")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)

screens_repository.add_page(
    "ErrorPage_1",
    Screen.create_error_screen(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "This site can’t be reached")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="error-information-popup-content"]/div[2][contains(text(), "ERR_INSUFFICIENT_RESOURCES")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)

screens_repository.add_page(
    "AbortedByUser",
    Screen.create_error_screen(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта прервано")]'),
        ],
        secondary_definitions=[
            (By.XPATH, '//section/div/div/div/div[contains(text(), "Вы нажали ")]'),
            (
                By.XPATH,
                "//section/div/div/div/div[contains(text(), "
                '"Если вы отклонили его по ошибке, попробуйте войти в аккаунт снова.")]',
            ),
        ],
        dispatcher=AbortedByUserDispatcher(),
    ),
)

screens_repository.add_page(
    "TooManyFailedAttempts",
    Screen.create_error_screen(
        main_definitions=[
            (
                By.XPATH,
                '//form/span/section[1]/div/div/div[1][contains(text(), "Слишком много попыток.")]',
            ),
        ],
        secondary_definitions=[
            # Слишком много неудачных попыток - есть почти на всех страницах ...
            (
                By.XPATH,
                '//h2/span[2][contains(text(), "Слишком много неудачных попыток")]',
            ),
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно '
                'вы пытаетесь войти в систему")]',
            ),
        ],
        dispatcher=TooManyFailedAttemptsDispatcher(),
    ),
)

screens_repository.add_page(
    "TooManyFailedAttempts_2",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Восстановление аккаунта")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//h2/span[2][contains(text(), "Слишком много неудачных попыток")]',
            ),
            (
                By.XPATH,
                "//form/span/section[2]/div/div/div[1][contains(text(), "
                '"Слишком много попыток. Вы сможете продолжить через несколько часов.")]',
            ),
        ],
        dispatcher=TooManyFailedAttemptsDispatcher(),
    ),
)

screens_repository.add_page(
    "InsecureBrowser",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            # Гуглу не нравится браузер и он не позволяет работать ...
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Возможно, этот браузер или приложение небезопасны.")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[2][contains(text(), "
                '"Попробуйте сменить браузер. ")]',
            ),
            (By.XPATH, '//*[@id="next"]/div/div/a'),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)


screens_repository.add_page(
    "BlockedPage",
    Screen.create_error_screen(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Заблокирован аккаунт")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[contains(text(), "
                '"Мы заметили необычные действия в вашем аккаунте Google '
                'и заблокировали его, чтобы защитить ваши данные. ")]',
            ),
            (By.XPATH, '//*[@id="accountRecoveryButton"]'),
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "RemovedPage",
    Screen.create_error_screen(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Аккаунт удален")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div[1][contains(text(), "
                '"Аккаунт был удален недавно, и его можно восстановить. ")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Использовать другой аккаунт")]'),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        dispatcher=UserFoundDispatcher(),
    ),
)

screens_repository.add_page(
    "EndPage",  # один экран для EndPage, EndPage2, EndPage3 и EndPage4.
    Screen.create_end_screen(
        main_definitions=[
            (
                By.XPATH,
                "//section/div/div/div/div[1][contains(text(), "
                '"Вы не предоставили Google достаточно информации, '
                'подтверждающей, что аккаунт принадлежит вам")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                '//a[contains(text(), "Ещё советы по восстановлению доступа к аккаунту")]',
            ),
            (By.XPATH, '//div/div/span[contains(text(), "Повторить попытку")]'),
        ],
    ),
)

screens_repository.add_page(
    "EndPage5",
    Screen.create_end_screen(
        main_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Google не удается распознать это устройство, и нам не хватает данных, '
                "чтобы убедиться, что это вы. "
                'В целях безопасности войти в аккаунт прямо сейчас нельзя.")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[2][contains(text(), "
                '"Попробуйте войти в аккаунт из местоположения или с устройства, '
                'где вы уже выполняли вход раньше. ")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
        ],
    ),
)

screens_repository.add_page(
    "EndPage6",
    Screen.create_end_screen(
        main_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[2][contains(text(), "
                '"Повторите попытку позже или попробуйте восстановить аккаунт Google.")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                '//*[@id="accountRecoveryButton"]/div/div/span[contains(text(), "Восстановить аккаунт")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Нам не удалось убедиться, что этот аккаунт принадлежит вам.")]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "EndPage7",
    Screen.create_end_screen(
        main_definitions=[
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[contains(text(), "
                '"Обратитесь за помощью к администратору домена. ")]',
            ),
        ],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                '//form/span/section/div/div/div/div/a[contains(text(), "Подробнее…")]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "EndPage8",
    Screen.create_end_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Нам не удалось убедиться, что этот аккаунт принадлежит вам.")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[2][contains(text(), "
                '"Повторите попытку позже или попробуйте восстановить аккаунт Google.")]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "EndPage9",
    Screen.create_end_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Чтобы восстановить доступ к аккаунту, свяжитесь с другим администратором вашего домена. ")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[2][contains(text(), "
                '"Вы также можете запросить проверку у службы поддержки Google Workspace. '
                'Проверка может занять до 72 часов.")]',
            ),
            (
                By.XPATH,
                '//*[@id="dasherManualSupportButton"]/div/div/a[@aria-label="Обратитесь в службу поддержки."]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "EndPage10",
    Screen.create_end_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Не удалось войти в аккаунт")]'),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[1][contains(text(), "
                '"Чтобы восстановить доступ к аккаунту, запросите проверку у службы поддержки Google Workspace. '
                'Проверка может занять до 72 часов. ")]',
            ),
            (
                By.XPATH,
                '//*[@id="dasherManualSupportButton"]/div/div/a[@aria-label="Обратитесь в службу поддержки."]',
            ),
        ],
    ),
)

screens_repository.add_page(
    "Error400Page",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="logo"][@aria-label="Google"]'),
            (By.XPATH, '//*[@id="af-error-container"]/p[1]/b[contains(text(), "400")]'),
            (
                By.XPATH,
                '//*[@id="af-error-container"]/p[1]/ins[contains(text(), "Произошла ошибка.")]',
            ),
            (
                By.XPATH,
                '//*[@id="af-error-container"]/p[2][contains(text(), '
                '"Сервер не может обработать запрос из-за синтаксических ошибок. '
                'Клиент не должен повторно отправлять этот запрос. ")]',
            ),
            (
                By.XPATH,
                '//*[@id="af-error-container"]/p[2]/ins[contains(text(), '
                '"Это все сведения, которыми мы располагаем.")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)


screens_repository.add_page(
    "ErrorCookiePage",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "Отключено сохранение файлов cookie")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div/div[contains(text(), "
                '"Включите файлы cookie в браузере и повторите попытку. ")]',
            ),
            (
                By.XPATH,
                '//*[@id="next"]/div/div/span[contains(text(), "Повторить попытку")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)

screens_repository.add_page(
    "ErrorPageProxy",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "This site can’t be reached")]',
            ),
            (
                By.XPATH,
                '//*[@id="main-message"]/p[contains(text(), " took too long to respond.")]',
            ),
            (
                By.XPATH,
                '//*[@id="suggestions-list"]/ul/li[1][contains(text(), "Checking the connection")]',
            ),
            (
                By.XPATH,
                '//*[@id="suggestions-list"]/ul/li[2]/a[contains(text(), "Checking the proxy and the firewall")]',
            ),
            (
                By.XPATH,
                '//*[@id="details-button"][contains(text(), "Details")]',
            ),
            (
                By.XPATH,
                '//*[@id="reload-button"][contains(text(), "Reload")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)

screens_repository.add_page(
    "ErrorPageProxy2",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "This site can’t be reached")]',
            ),
            (
                By.XPATH,
                '//*[@id="main-message"]/p[contains(text(), "The webpage at ")]',
            ),
            (
                By.XPATH,
                '//*[@id="error-information-popup-content"]/div[2][contains(text(), "ERR_SOCKET_NOT_CONNECTED")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)
