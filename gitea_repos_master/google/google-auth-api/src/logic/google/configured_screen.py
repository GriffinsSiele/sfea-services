from src.browser.google_selenium_browser import GoogleSeleniumBrowser
from src.interfaces.abstract_browser import AbstractBrowser
from src.interfaces.abstract_screen_dispatcher import AbstractScreenDispatcher
from src.logic.google.screen import BaseScreen, Definition


class ConfiguredScreen(BaseScreen):
    browser: AbstractBrowser = GoogleSeleniumBrowser()


class Screen:
    @staticmethod
    def create_screen_with_payload(
        main_definitions: Definition,
        secondary_definitions: Definition,
        follow: list[dict],
        dispatcher: AbstractScreenDispatcher,
    ) -> ConfiguredScreen:
        return ConfiguredScreen(
            main_definitions=main_definitions,
            secondary_definitions=secondary_definitions,
            follow=follow,
            dispatcher=dispatcher,
        )

    @staticmethod
    def create_screen_without_payload(
        main_definitions: Definition,
        secondary_definitions: Definition,
        follow: list[dict],
    ) -> ConfiguredScreen:
        return ConfiguredScreen(
            main_definitions=main_definitions,
            secondary_definitions=secondary_definitions,
            follow=follow,
        )

    @staticmethod
    def create_error_screen(
        main_definitions: Definition,
        secondary_definitions: Definition,
        dispatcher: AbstractScreenDispatcher,
    ) -> ConfiguredScreen:
        return ConfiguredScreen(
            main_definitions=main_definitions,
            secondary_definitions=secondary_definitions,
            dispatcher=dispatcher,
        )

    @staticmethod
    def create_end_screen(
        main_definitions: Definition,
        secondary_definitions: Definition,
    ) -> ConfiguredScreen:
        return ConfiguredScreen(
            main_definitions=main_definitions, secondary_definitions=secondary_definitions
        )
