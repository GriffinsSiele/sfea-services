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
NAME_PAGE_TITLE = "NamePage"
MAIN_PAGE_INPUT = "recoveryIdentifierId"
CAPTCHA_SCREENS_FOR_SOLVING = ("CaptchaPage_1",)
MAIN_SIMILAR_SCREENS: list = []

screens_repository_name = ScreensRepository()

screens_repository_name.add_page(
    MAIN_PAGE_TITLE,
    Screen.create_screen_without_payload(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Ваш адрес электронной почты")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Укажите номер телефона или резервный адрес электронной почты")]',
            ),
            (By.XPATH, '//*[@id="recoveryIdentifierId"]'),
        ],
        follow=[
            {
                "link": (
                    By.XPATH,
                    '//*[@id="queryPhoneNext"]/div/button/span[contains(text(), "Далее")]',
                ),
                "screens": [
                    NAME_PAGE_TITLE,
                    "InsecureBrowser",
                ],
            }
        ],
    ),
)

screens_repository_name.add_page(
    NAME_PAGE_TITLE,
    Screen.create_screen_without_payload(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Как вас зовут?")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Укажите свое имя и фамилию для аккаунта Google")]',
            ),
            (By.XPATH, '//*[@id="firstName"]'),
            (By.XPATH, '//*[@id="lastName"]'),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": ["NotFoundError_1", "FoundEmailAlert_1", "FoundPhoneAlert_1"],
            }
        ],
    ),
)

screens_repository_name.add_page(
    "NotificationNotSent",
    Screen.create_screen_without_payload(
        main_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Подтверждение не отправлено")]'),
        ],
        secondary_definitions=[
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Произошла ошибка. Повторите попытку.")]',
            ),
            (By.XPATH, '//button/span[contains(text(), "Далее")]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": ["NotFoundError_1"],
            }
        ],
    ),
)

screens_repository_name.add_page(
    "NotFoundError_1",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Аккаунт не найден")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), "Такого аккаунта Google нет.")]',
            ),
            (
                By.XPATH,
                '//*[@id="noaccountsNext"]/div/button/span[contains(text(), "Повторить попытку")]',
            ),
        ],
        dispatcher=NotFoundErrorDispatcher(),
    ),
)

screens_repository_name.add_page(
    "SourceIncorrectData",
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

screens_repository_name.add_page(
    "FoundEmailAlert_1",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Получите код подтверждения")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, '
                'что это действительно вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                "//form/span/section/div/div/div[1][contains(text(), "
                '"Мы отправим код подтверждения на адрес ")]',
            ),
            (
                By.XPATH,
                '//*[@id="idvpreregisteredemailNext"]/div/button/span[contains(text(), "Отправить")]',
            ),
        ],
        dispatcher=UserFoundEmailDispatcher(),
    ),
)

screens_repository_name.add_page(
    "FoundPhoneAlert_1",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//h1/span[contains(text(), "Получите код подтверждения")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, '
                'что это действительно вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//form/span/section/header/div/h2/span[contains(text(), "Получите код подтверждения")]',
            ),
            (
                By.XPATH,
                '//form/span/section/div/div/div[contains(text(), "Код подтверждения будет отправлен на номер ")]',
            ),
            (
                By.XPATH,
                '//button/span[contains(text(), "Отправить")]',
            ),
        ],
        dispatcher=UserFoundPhoneDispatcher(),
    ),
)

screens_repository_name.add_page(
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
            (By.XPATH, '//*[@id="next"]'),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)

screens_repository_name.add_page(
    "CaptchaPage_1",
    Screen.create_screen_with_payload(
        main_definitions=[],
        secondary_definitions=[
            (By.XPATH, '//*[@id="captchaimg" and @src]'),
            (By.XPATH, '//h1/span[contains(text(), "Проблемы с входом в аккаунт?")]'),
            (
                By.XPATH,
                '//*[@id="headingSubtext"]/span[contains(text(), '
                '"Для защиты аккаунта мы должны убедиться, что это действительно вы пытаетесь войти в систему")]',
            ),
            (
                By.XPATH,
                '//form/span/section/div/div/div[1][contains(text(), "Докажите, что вы не робот")]',
            ),
            (By.XPATH, '//input[@type="text"]'),
        ],
        follow=[
            {
                "link": (By.XPATH, '//button/span[contains(text(), "Далее")]'),
                "screens": ["NotFoundError_1", "FoundEmailAlert_1", "FoundPhoneAlert_1"],
            }
        ],
        dispatcher=CaptchaDispatcher(),
    ),
)

screens_repository_name.add_page(
    "ErrorPageProxy",
    Screen.create_error_screen(
        main_definitions=[],
        secondary_definitions=[
            (
                By.XPATH,
                '//h1/span[contains(text(), "No internet")]',
            ),
            (
                By.XPATH,
                '//*[@id="main-message"]/p[contains(text(), "There is something wrong with the proxy server, or the address is incorrect.")]',
            ),
            (
                By.XPATH,
                '//*[@id="suggestions-list"]/ul/li[1][contains(text(), "Contacting the system admin")]',
            ),
            (
                By.XPATH,
                '//*[@id="suggestions-list"]/ul/li[2]/a[contains(text(), "Checking the proxy address")]',
            ),
            (
                By.XPATH,
                '//*[@id="error-information-popup-content"]/div[2][contains(text(), "ERR_PROXY_CONNECTION_FAILED")]',
            ),
            (
                By.XPATH,
                '//*[@id="details-button"][contains(text(), "Details")]',
            ),
        ],
        dispatcher=SessionBlockedDispatcher(),
    ),
)
