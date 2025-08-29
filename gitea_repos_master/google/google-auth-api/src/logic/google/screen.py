from selenium.webdriver.common.by import By

from src.interfaces.abstract_browser import AbstractBrowser
from src.logic.google.screen_dispatchers import AbstractScreenDispatcher

Definition = list[tuple[By, str]]


class BaseScreen:
    browser: AbstractBrowser | None = None

    def __init__(
        self,
        main_definitions: Definition | None = None,
        secondary_definitions: Definition | None = None,
        follow: list[dict] | None = None,
        dispatcher: AbstractScreenDispatcher | None = None,
    ):
        """
        Экран страницы. Содержит свойства и методы для работы с ним.

        :param main_definitions: основные теги, которые позволяют отличить данный экран от других.
        :param secondary_definitions: дополнительные теги, которые позволяют отличить данный экран от других.
        :param follow: ссылки для перехода на следующие экраны и список экранов, на которые можно перейти по данной ссылке.
        :param dispatcher: обработчик, собирает данные с экрана.
        """
        self.main_definitions = main_definitions or []
        self.secondary_definitions = secondary_definitions or []
        self.follow = follow or []
        self.dispatcher = dispatcher

    def check_on_other_screen(
        self, other_screen_main_definitions: Definition
    ) -> tuple[By, str] | None:
        if not self.browser:
            raise ValueError("Browser not defined")
        for definition in other_screen_main_definitions:
            if self.browser.get_loaded_element(*definition):
                return definition
        return None

    @property
    def is_loaded_screen(self) -> bool:
        if not self.browser:
            raise ValueError("Browser not defined")
        for definition in self.main_definitions + self.secondary_definitions:
            if not self.browser.get_loaded_element(*definition):
                return False
        return True

    @property
    def is_end_page(self) -> bool:
        next_screens = []
        for item in self.follow:
            if screen := item.get("screens"):
                next_screens += screen
        return not next_screens

    @property
    def has_useful_data(self) -> bool:
        return bool(self.dispatcher)

    def extract_data(self) -> dict[str, list | bool] | None:
        return (
            self.dispatcher.get_data(self.browser)
            if self.dispatcher and self.browser
            else None
        )
