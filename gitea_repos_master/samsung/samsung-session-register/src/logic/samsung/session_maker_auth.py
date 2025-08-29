from isphere_exceptions.source import SourceOperationFailure
from isphere_exceptions.worker import InternalWorkerError

from src.config.app import ConfigApp
from src.logic.samsung.screen_definitions_auth import (
    INPUT_FIELD,
    MAIN_SCREEN_DEFINITION,
    MAIN_SCREEN_DEFINITION_AUTH,
    MAIN_SCREEN_DEFINITION_BUTTON,
    RESULT_SCREEN_DEFINITION,
    SEARCH_BUTTON,
    SEARCH_BUTTON_DISABLED,
)
from src.logic.samsung.session_maker_common import SessionMakerCommon
from src.utils import informer


class SessionMakerAuth(SessionMakerCommon):
    """Генерирует сессию для auth"""

    start_url = ConfigApp.auth.START_URL
    session_url = ConfigApp.auth.SESSION_URL

    search_button = SEARCH_BUTTON
    search_button_disabled = SEARCH_BUTTON_DISABLED
    main_screen_definition = MAIN_SCREEN_DEFINITION
    main_screen_definition_button = MAIN_SCREEN_DEFINITION_BUTTON
    result_screen_definition = RESULT_SCREEN_DEFINITION

    main_screen_definition_auth = MAIN_SCREEN_DEFINITION_AUTH

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

        # Сначала загружаем главную страницу samsung
        # иначе не будет необходимых кук и страница auth не загрузится.
        await super()._load_main_page_and_check()

        # Теперь загружаем главную страницу auth
        self.browser.wait_recaptcha_to_load()
        self.browser.get_element_and_click(*self.main_screen_definition_button)
        self.browser.load_page_waiting(self.main_screen_definition_auth)
        if not self.browser.get_element(*self.main_screen_definition_auth):
            raise SourceOperationFailure("Failed to load main page")

    @informer(3, "Set data")
    async def _set_search_data(self, data: str) -> None:
        """Устанавливает данные для поиска

        :param data: Данные для поиска (заведомо не существующий аккаунт)
        :return: None
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_set_search_data"'
            )
        self.browser.get_element_and_set_data(*INPUT_FIELD, data)
