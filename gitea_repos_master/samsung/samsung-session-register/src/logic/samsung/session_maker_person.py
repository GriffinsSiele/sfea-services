from isphere_exceptions.source import SourceOperationFailure
from isphere_exceptions.worker import InternalWorkerError
from pydash import get
from selenium.webdriver.common.by import By

from src.config.app import ConfigApp
from src.logic.samsung.screen_definitions_auth import (
    MAIN_SCREEN_DEFINITION as MAIN_SCREEN_DEFINITION_AUTH,
)
from src.logic.samsung.screen_definitions_person import (
    MAIN_SCREEN_DEFINITION_PERSON,
    RESULT_SCREEN_DEFINITION_PERSON,
    SEARCH_BUTTON_DISABLED_PERSON,
    SEARCH_BUTTON_PERSON,
)
from src.logic.samsung.session_maker_common import SessionMakerCommon
from src.utils import informer


class SessionMakerPerson(SessionMakerCommon):
    """Генерирует сессию для person"""

    start_url = ConfigApp.auth.START_URL
    session_url = ConfigApp.person.SESSION_URL

    search_button = SEARCH_BUTTON_PERSON
    search_button_disabled = SEARCH_BUTTON_DISABLED_PERSON
    main_screen_definition = MAIN_SCREEN_DEFINITION_AUTH
    result_screen_definition = RESULT_SCREEN_DEFINITION_PERSON

    main_screen_definition_person = MAIN_SCREEN_DEFINITION_PERSON

    @informer(2.1, "Loading the main page extended")
    async def _load_main_page_and_check(self) -> None:
        """Загружает главную страницу и проверяет, что она была загружена корректно.
        Возбуждает исключение SourceOperationFailure в случае ошибки.

        :return: None
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_load_main_page_and_check"'
            )

        # Сначала загружаем главную страницу auth
        # иначе не будет необходимых кук и страница person не загрузится.
        await super()._load_main_page_and_check()

        # Теперь загружаем главную страницу person
        self.browser.get(ConfigApp.person.START_URL)
        self.browser.load_page_waiting(self.main_screen_definition_person)
        if not self.browser.get_element(*self.main_screen_definition_person):
            raise SourceOperationFailure("Failed to load main page")

    @informer(3, "Set data")
    async def _set_search_data(self, data: dict) -> None:
        """Устанавливает данные для поиска

        :param data: Данные для поиска (заведомо не существующий аккаунт)
        :return: None
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_set_search_data"'
            )
        self.browser.get_element_and_set_data(
            By.XPATH, '//*[@id="givenName1"]', get(data, "first_name", "Ivan")
        )
        self.browser.get_element_and_set_data(
            By.XPATH, '//*[@id="familyName1"]', get(data, "last_name", "Ivanov12345")
        )
        self.browser.get_element_and_set_data(
            By.XPATH, '//*[@id="day"]', get(data, "birthdate.day", "10")
        )
        # Принимает строку из списка:
        # 'Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'
        self.browser.get_select_element_and_set_data(
            By.XPATH, '//*[@id="month"]', get(data, "birthdate.month", "Январь")
        )
        self.browser.get_element_and_set_data(
            By.XPATH, '//*[@id="year"]', get(data, "birthdate.year", "1990")
        )
