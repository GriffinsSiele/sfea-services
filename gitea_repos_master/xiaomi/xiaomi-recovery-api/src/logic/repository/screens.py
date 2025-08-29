from isphere_exceptions.worker import InternalWorkerError
from selenium.webdriver.common.by import By
from undetected_chromedriver import WebElement

from src.interfaces.abstract_browser import AbstractBrowser
from src.logger import logging

WebElementDef = tuple[By, str]


class Screen:
    def __init__(
        self,
        browser: AbstractBrowser,
        definitions: list[WebElementDef],
        input_fields: list[WebElementDef] | None = None,
        payloads: list[WebElementDef] | None = None,
        buttons: list[WebElementDef] | None = None,
        extra_info: list[tuple[str, WebElementDef]] | None = None,
    ) -> None:
        self.browser = browser
        self.definitions = definitions
        self.input_fields = input_fields
        self.payloads = payloads
        self.buttons = buttons
        self.extra_info = extra_info

    def is_current_screen(self) -> bool:
        for definition in self.definitions:
            if self.browser.get_loaded_element(*definition):
                return True
        return False

    def get_payload_as_web_element(self) -> WebElement | None:
        if not self.payloads:
            raise InternalWorkerError('The "payloads" not defined')

        for payload in self.payloads:
            if web_element := self.browser.get_loaded_element(*payload):
                return web_element
        return None

    def get_extra_info(self) -> dict:
        if not self.extra_info:
            raise InternalWorkerError('The "extra_info" not defined')

        for key, extra in self.extra_info:
            if web_element := self.browser.get_loaded_element(*extra):
                if text := web_element.text:
                    return {key: [text]}
        return {}

    def get_payload_as_bytes(self) -> bytes | None:
        if web_element := self.get_payload_as_web_element():
            return web_element.screenshot_as_png
        return None

    def click_button(self) -> None:
        if not self.buttons:
            raise InternalWorkerError('The "buttons" not defined')

        for button in self.buttons:
            if self.browser.get_loaded_element(*button):
                self.browser.get_element_and_click(*button)
                return None

    def set_data_to_input_field_and_press_enter(self, data: str) -> None:
        if not self.input_fields:
            raise InternalWorkerError('The "input_fields" not defined')

        for input_field in self.input_fields:
            if self.browser.get_loaded_element(*input_field):
                self.browser.get_element_set_data_and_enter(*input_field, data)
                return None
        logging.warning("Web element to set data not found")
