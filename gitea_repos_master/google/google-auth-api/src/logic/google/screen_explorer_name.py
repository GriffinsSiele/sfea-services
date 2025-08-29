from isphere_exceptions.source import SourceIncorrectDataDetected
from selenium.webdriver.common.by import By

from src.logic.google.connections_name import (
    CAPTCHA_SCREENS_FOR_SOLVING,
    MAIN_PAGE_INPUT,
    MAIN_PAGE_TITLE,
    MAIN_SIMILAR_SCREENS,
    NAME_PAGE_TITLE,
)
from src.logic.google.screen_explorer import ScreensExplorer


class ScreensNameExplorer(ScreensExplorer):
    main_page_title = MAIN_PAGE_TITLE
    main_page_input = MAIN_PAGE_INPUT
    name_page_title = NAME_PAGE_TITLE
    main_similar_screens = MAIN_SIMILAR_SCREENS
    captcha_screens_for_solving = CAPTCHA_SCREENS_FOR_SOLVING

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.auth_data = {}

    async def search(self, payload: dict) -> dict:
        """Запускает поиск.

        :param payload: словарь, который содержит данные для авторизации (ФИО, email и/или телефон).
        :return: словарь с результатами поиска.
        """
        _auth_data = self._prepare_input_data(payload)

        phone_or_email = _auth_data.get("email")
        if not phone_or_email:
            phone_or_email = _auth_data.get("phone")
        if not phone_or_email or not _auth_data.get("first_name"):
            raise SourceIncorrectDataDetected()
        self.auth_data = _auth_data

        return await super().search(phone_or_email)

    def _processing_the_current_screen(self, screen_title: str) -> None:
        super()._processing_the_current_screen(screen_title)
        if screen_title != self.name_page_title:
            return None
        self.browser.get_element_and_set_data(
            By.ID, "firstName", self.auth_data.get("first_name")
        )
        if last_name := self.auth_data.get("last_name"):
            self.browser.get_element_and_set_data(By.ID, "lastName", last_name)

    @staticmethod
    def _prepare_input_data(input_data: dict) -> dict:
        return input_data
