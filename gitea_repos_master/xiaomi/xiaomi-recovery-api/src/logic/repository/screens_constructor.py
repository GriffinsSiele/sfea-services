from src.interfaces.abstract_browser import AbstractBrowser
from src.logic.repository.screens import Screen, WebElementDef


class ScreenConstructor:
    @staticmethod
    def main_screen(
        browser: AbstractBrowser,
        definitions: list[WebElementDef],
        input_fields: list[WebElementDef],
    ) -> Screen:
        return Screen(
            browser=browser,
            definitions=definitions,
            input_fields=input_fields,
            payloads=None,
        )

    @staticmethod
    def captcha_screen(
        browser: AbstractBrowser,
        definitions: list[WebElementDef],
        input_fields: list[WebElementDef],
        payloads: list[WebElementDef],
        buttons: list[WebElementDef],
    ) -> Screen:
        return Screen(
            browser=browser,
            definitions=definitions,
            input_fields=input_fields,
            payloads=payloads,
            buttons=buttons,
        )

    @staticmethod
    def result_screen(
        browser: AbstractBrowser,
        definitions: list[WebElementDef],
    ) -> Screen:
        return Screen(
            browser=browser,
            definitions=definitions,
        )

    @staticmethod
    def result_screen_found(
        browser: AbstractBrowser,
        definitions: list[WebElementDef],
        extra_info: list[tuple[str, WebElementDef]],
    ) -> Screen:
        return Screen(
            browser=browser,
            definitions=definitions,
            extra_info=extra_info,
        )
