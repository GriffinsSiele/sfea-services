from isphere_exceptions.worker import InternalWorkerError
from pydash import get
from selenium.webdriver.common.by import By

from src.config.app import ConfigApp
from src.logic.samsung.screen_definitions_name import (
    CONTINUE_BUTTON_DISABLED_NAME,
    CONTINUE_BUTTON_NAME,
    MAIN_SCREEN_DEFINITION_NAME,
    MONTH_FIELD_DEFINITION,
    RESULT_SCREEN_DEFINITION_NAME,
    SEARCH_BUTTON_DISABLED_NAME,
    SEARCH_BUTTON_NAME,
)
from src.logic.samsung.session_maker_common import SessionMakerCommon
from src.utils import informer


class SessionMakerName(SessionMakerCommon):
    """Генерирует сессию для name"""

    start_url = ConfigApp.name.START_URL
    session_url = ConfigApp.name.SESSION_URL

    continue_button = CONTINUE_BUTTON_NAME
    continue_button_disabled = CONTINUE_BUTTON_DISABLED_NAME

    search_button = SEARCH_BUTTON_NAME
    search_button_disabled = SEARCH_BUTTON_DISABLED_NAME
    main_screen_definition = MAIN_SCREEN_DEFINITION_NAME

    result_screen_definition = RESULT_SCREEN_DEFINITION_NAME

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
            By.XPATH,
            '//*[@id="recoveryId"]',
            get(data, "account_login", "I.Ivanov@yandex.ru"),
        )

        self.browser.wait_hidden_element_and_click(
            self.continue_button_disabled, self.continue_button
        )

        # ожидание загрузки элемента с выпадающим списком месяцев
        self.browser.wait_element(MONTH_FIELD_DEFINITION)

        self.set_user_data(data)

    def set_user_data(self, data: dict) -> None:
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
        # 'Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'
        self.browser.get_select_element_and_set_data(
            By.XPATH, '//*[@id="month"]', get(data, "birthdate.moon", "Январь")
        )
        self.browser.get_element_and_set_data(
            By.XPATH, '//*[@id="year"]', get(data, "birthdate.year", "1990")
        )
